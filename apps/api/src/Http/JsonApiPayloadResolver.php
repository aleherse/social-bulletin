<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Attribute\JsonApiPayload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Hands a `#[JsonApiPayload]` argument the `data.attributes` of the request's JSON:API resource object.
 */
final readonly class JsonApiPayloadResolver implements ValueResolverInterface
{
    /**
     * @return iterable<array<string, mixed>>
     *
     * @throws InvalidJsonApiRequest when the body is not a resource document of the expected type
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $payload = $argument->getAttributes(JsonApiPayload::class, ArgumentMetadata::IS_INSTANCEOF)[0] ?? null;

        if (! $payload instanceof JsonApiPayload) {
            return [];
        }

        $mediaType = trim(explode(';', (string) $request->headers->get('Content-Type'))[0]);

        if (JsonApi::CONTENT_TYPE !== $mediaType) {
            throw new InvalidJsonApiRequest(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'error.unsupported_media_type');
        }

        try {
            $document = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidJsonApiRequest(Response::HTTP_BAD_REQUEST, 'error.malformed_json');
        }

        $resource = \is_array($document) ? $document['data'] ?? null : null;

        if (! \is_array($resource) || ! \is_string($resource['type'] ?? null)) {
            throw new InvalidJsonApiRequest(Response::HTTP_BAD_REQUEST, 'error.invalid_resource_document');
        }

        $routeId = $request->attributes->get('id');

        if ($resource['type'] !== $payload->type
            || (isset($resource['id']) && \is_string($routeId) && $resource['id'] !== $routeId)) {
            throw new InvalidJsonApiRequest(Response::HTTP_CONFLICT, 'error.resource_mismatch');
        }

        $attributes = $resource['attributes'] ?? [];

        if (! \is_array($attributes)) {
            throw new InvalidJsonApiRequest(Response::HTTP_BAD_REQUEST, 'error.invalid_resource_document');
        }

        return [$attributes];
    }
}
