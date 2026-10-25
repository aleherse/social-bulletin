<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Webmozart\Assert\Assert;

use function JmesPath\search;

final class OpenApiContext implements Context
{
    private const MEDIA_TYPE = 'application/vnd.api+json';

    public function __construct(
        private readonly ApiClient $apiClient,
    ) {
    }

    #[Then('the OpenAPI document should describe the :status response of :method :path as a jsonapi :cardinality of :schema')]
    public function theOpenApiDocumentShouldDescribeTheResponseAs(
        int $status,
        string $method,
        string $path,
        string $cardinality,
        string $schema,
    ): void {
        Assert::inArray($cardinality, ['resource', 'list']);

        $data = $this->operationSchema($method, $path, $status)['properties']['data'] ?? null;
        Assert::isArray($data);

        $resource = 'list' === $cardinality ? $data['items'] ?? null : $data;
        Assert::isArray($resource);

        if ('list' === $cardinality) {
            Assert::same($data['type'] ?? null, 'array');
        }

        Assert::same($resource['required'] ?? null, ['id', 'type', 'attributes']);
        Assert::same($resource['properties']['attributes']['$ref'] ?? null, '#/components/schemas/'.$schema);
    }

    #[Then('the OpenAPI document should describe a :type request body for :method :path')]
    public function theOpenApiDocumentShouldDescribeARequestBody(string $type, string $method, string $path): void
    {
        $schema = $this->document()['paths'][$path][strtolower($method)]['requestBody']['content'][self::MEDIA_TYPE]['schema'] ?? null;
        Assert::isArray($schema, sprintf('%s %s has no %s request body.', $method, $path, self::MEDIA_TYPE));

        Assert::same($schema['properties']['data']['properties']['type']['example'] ?? null, $type);
    }

    #[Then('the OpenAPI request attributes of :method :path should be :properties')]
    public function theOpenApiRequestAttributesShouldBe(string $method, string $path, string $properties): void
    {
        $schema = $this->document()['paths'][$path][strtolower($method)]['requestBody']['content'][self::MEDIA_TYPE]['schema'] ?? null;
        Assert::isArray($schema);

        Assert::same(
            array_keys($schema['properties']['data']['properties']['attributes']['properties'] ?? []),
            array_map(trim(...), explode(',', $properties)),
        );
    }

    #[Then('the OpenAPI document should describe the :status error response of :method :path')]
    public function theOpenApiDocumentShouldDescribeTheErrorResponse(string $status, string $method, string $path): void
    {
        $schema = $this->document()['paths'][$path][strtolower($method)]['responses'][$status]['content'][self::MEDIA_TYPE]['schema'] ?? null;

        Assert::same($schema['$ref'] ?? null, '#/components/schemas/JsonApiErrors');
    }

    #[Then('the OpenAPI document should require the :name cookie by default')]
    public function theOpenApiDocumentShouldRequireTheCookieByDefault(string $name): void
    {
        $document = $this->document();

        Assert::same($document['security'] ?? null, [['cookieAuth' => []]]);
        Assert::same(
            $document['components']['securitySchemes']['cookieAuth'] ?? null,
            [
                'type' => 'apiKey',
                'description' => 'The httpOnly `token` cookie set by `POST /api/session`. Sign in there first: the browser stores and sends the cookie itself, so the value field below is not used.',
                'name' => $name,
                'in' => 'cookie',
            ],
        );
    }

    #[Then('the OpenAPI document should not require authentication for :method :path')]
    public function theOpenApiDocumentShouldNotRequireAuthentication(string $method, string $path): void
    {
        Assert::same($this->document()['paths'][$path][strtolower($method)]['security'] ?? null, []);
    }

    #[Then('the OpenAPI document should require authentication for :method :path')]
    public function theOpenApiDocumentShouldRequireAuthentication(string $method, string $path): void
    {
        Assert::keyNotExists($this->document()['paths'][$path][strtolower($method)], 'security');
    }

    #[Then('the OpenAPI document should say the :status response of :method :path sets a cookie')]
    public function theOpenApiDocumentShouldSayTheResponseSetsACookie(int $status, string $method, string $path): void
    {
        $header = $this->document()['paths'][$path][strtolower($method)]['responses'][(string) $status]['headers']['Set-Cookie'] ?? null;

        Assert::isArray($header, sprintf('%s %s documents no Set-Cookie header on its %d response.', $method, $path, $status));
        Assert::stringNotEmpty($header['description'] ?? null);
    }

    #[Then('the OpenAPI document should not describe a :status response of :method :path')]
    public function theOpenApiDocumentShouldNotDescribeAResponse(int $status, string $method, string $path): void
    {
        $responses = $this->document()['paths'][$path][strtolower($method)]['responses'] ?? [];

        Assert::keyNotExists($responses, (string) $status);
    }

    #[Then('the OpenAPI schema :schema should have the properties :properties')]
    public function theOpenApiSchemaShouldHaveTheProperties(string $schema, string $properties): void
    {
        $definition = $this->schema($schema);

        Assert::same(
            array_keys($definition['properties'] ?? []),
            array_map(trim(...), explode(',', $properties)),
        );
    }

    #[Then('the OpenAPI schema :schema should not have a property :property')]
    public function theOpenApiSchemaShouldNotHaveAProperty(string $schema, string $property): void
    {
        $definition = $this->schema($schema);

        Assert::keyNotExists($definition['properties'] ?? [], $property);
        Assert::false(in_array($property, $definition['required'] ?? [], true));
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        return $this->apiClient->decodedResponse();
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(string $name): array
    {
        $schema = search('components.schemas', $this->document())[$name] ?? null;
        Assert::isArray($schema, sprintf('Schema "%s" is not in the OpenAPI document.', $name));

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function operationSchema(string $method, string $path, int $status): array
    {
        $schema = $this->document()['paths'][$path][strtolower($method)]['responses'][(string) $status]['content'][self::MEDIA_TYPE]['schema'] ?? null;
        Assert::isArray($schema, sprintf('%s %s has no %d %s response.', $method, $path, $status, self::MEDIA_TYPE));

        return $schema;
    }
}
