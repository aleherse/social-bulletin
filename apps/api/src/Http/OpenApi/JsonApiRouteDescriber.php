<?php

declare(strict_types=1);

namespace App\Http\OpenApi;

use App\Http\Attribute\JsonApi as JsonApiAttribute;
use App\Http\Attribute\JsonApiPayload;
use App\Http\JsonApi;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Route;
use Symfony\Component\TypeInfo\Type;
use Webmozart\Assert\Assert;

/**
 * Documents the success response of every `#[JsonApi]` controller from the attribute itself,
 * with the resource attributes described from the model its transformer accepts.
 */
final class JsonApiRouteDescriber implements RouteDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;
    use RouteDescriberTrait;

    private const SERVER_OWNED = ['id', 'authorId'];

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        $attribute = ($reflectionMethod->getAttributes(JsonApiAttribute::class)
            ?: $reflectionMethod->getDeclaringClass()
                ->getAttributes(JsonApiAttribute::class))[0] ?? null;

        if (null === $attribute) {
            return;
        }

        $jsonApi = $attribute->newInstance();
        $isCollection = $this->returnsList($reflectionMethod);

        $payload = $this->payload($reflectionMethod);

        foreach ($this->getOperations($api, $route) as $operation) {
            if (null !== $payload) {
                $this->describeRequest($operation, $payload);
            }

            $this->describeErrors($operation);

            $response = Util::getIndexedCollectionItem($operation, OA\Response::class, (string) $jsonApi->status);
            $response->description = 'The '.$jsonApi->type.' JSON:API document.';
            $mediaType = Util::getCollectionItem($response, OA\MediaType::class, [
                'mediaType' => JsonApi::CONTENT_TYPE,
            ]);
            Util::getChild($mediaType, OA\Schema::class, [
                'type' => 'object',
                'required' => ['data'],
                'properties' => [
                    new OA\Property($isCollection
                        ? [
                            'property' => 'data',
                            'type' => 'array',
                            'items' => new OA\Items($this->resource($jsonApi)),
                        ]
                        : [
                            'property' => 'data',
                        ] + $this->resource($jsonApi)),
                ],
            ]);
        }
    }

    private function describeRequest(OA\Operation $operation, JsonApiPayload $payload): void
    {
        $body = Util::getChild($operation, OA\RequestBody::class, [
            'required' => true,
        ]);
        $mediaType = Util::getCollectionItem($body, OA\MediaType::class, [
            'mediaType' => JsonApi::CONTENT_TYPE,
        ]);
        Util::getChild($mediaType, OA\Schema::class, [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                new OA\Property([
                    'property' => 'data',
                    'type' => 'object',
                    'required' => ['type', 'attributes'],
                    'properties' => [
                        new OA\Property([
                            'property' => 'type',
                            'type' => 'string',
                            'example' => $payload->type,
                        ]),
                        new OA\Property([
                            'property' => 'attributes',
                            'type' => 'object',
                            'description' => 'Every field is optional here: the domain validates them and answers 400 per field.',
                            'properties' => $this->commandProperties($payload->command),
                        ]),
                    ],
                ]),
            ],
        ]);
    }

    /**
     * Every endpoint can fail with a JSON:API error document, whatever the exact 4xx status.
     */
    private function describeErrors(OA\Operation $operation): void
    {
        $response = Util::getIndexedCollectionItem($operation, OA\Response::class, '4XX');
        $response->description = 'A JSON:API error document.';
        $mediaType = Util::getCollectionItem($response, OA\MediaType::class, [
            'mediaType' => JsonApi::CONTENT_TYPE,
        ]);
        Util::getChild($mediaType, OA\Schema::class, [
            'ref' => '#/components/schemas/JsonApiErrors',
        ]);
    }

    private function payload(\ReflectionMethod $method): ?JsonApiPayload
    {
        foreach ($method->getParameters() as $parameter) {
            $payload = $parameter->getAttributes(JsonApiPayload::class)[0] ?? null;

            if (null !== $payload) {
                return $payload->newInstance();
            }
        }

        return null;
    }

    /**
     * The request fields are the command's public properties, bar the ones the server fills in.
     *
     * @param class-string $command
     *
     * @return list<OA\Property>
     */
    private function commandProperties(string $command): array
    {
        $properties = [];

        foreach ((new \ReflectionClass($command))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $type = $property->getType();

            if (\in_array($property->getName(), self::SERVER_OWNED, true) || ! $type instanceof \ReflectionNamedType) {
                continue;
            }

            Assert::same($type->getName(), 'string', sprintf('%s::$%s is not a string field.', $command, $property->getName()));

            $properties[] = new OA\Property([
                'property' => $property->getName(),
                'type' => 'string',
                'nullable' => $type->allowsNull(),
            ]);
        }

        return $properties;
    }

    /**
     * @return array<string, mixed> the resource object's schema keys
     */
    private function resource(JsonApiAttribute $jsonApi): array
    {
        $transformed = (new \ReflectionMethod($jsonApi->transformer, 'transform'))->getParameters()[0]
            ->getType();
        Assert::isInstanceOf($transformed, \ReflectionNamedType::class);
        $model = $transformed->getName();
        Assert::classExists($model);

        return [
            'type' => 'object',
            'required' => ['id', 'type', 'attributes'],
            'properties' => [
                new OA\Property([
                    'property' => 'id',
                    'type' => 'string',
                ]),
                new OA\Property([
                    'property' => 'type',
                    'type' => 'string',
                    'example' => $jsonApi->type,
                ]),
                new OA\Property([
                    'property' => 'attributes',
                    'ref' => $this->modelRegistry->register(new Model(
                        Type::object($model),
                        name: $this->attributesName($model),
                    )),
                ]),
            ],
        ];
    }

    /**
     * @param class-string $model
     *
     * @return non-empty-string
     */
    private function attributesName(string $model): string
    {
        $name = (new \ReflectionClass($model))->getShortName().JsonApiAttributesModelDescriber::NAME_SUFFIX;
        Assert::stringNotEmpty($name);

        return $name;
    }

    private function returnsList(\ReflectionMethod $method): bool
    {
        return 'array' === (string) $method->getReturnType();
    }
}
