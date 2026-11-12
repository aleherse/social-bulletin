<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use App\Tests\Integration\IntegrationTestCase;
use Nelmio\ApiDocBundle\Render\RenderOpenApi;

/**
 * What `nelmio_api_doc.yaml` adds to the attributes, and what the attributes document (application-framework-0008).
 */
final class OpenApiDocumentTest extends IntegrationTestCase
{
    private const MEDIA_TYPE = 'application/vnd.api+json';

    /**
     * @var array<string, mixed>
     */
    private array $document;

    protected function setUp(): void
    {
        parent::setUp();

        $render = self::getContainer()->get('nelmio_api_doc.render_docs');
        self::assertInstanceOf(RenderOpenApi::class, $render);

        $this->document = json_decode($render->render(RenderOpenApi::JSON, 'default'), true, flags: JSON_THROW_ON_ERROR);
    }

    public function testASingleResourceResponseIsDocumentedAsAJsonApiResource(): void
    {
        $this->assertResponseIsResource('GET', '/api/movements/{id}', 200, 'MovementAttributes');
        $this->assertResponseIsResource('POST', '/api/movements', 201, 'MovementAttributes');
    }

    public function testACollectionResponseIsDocumentedAsAListOfJsonApiResources(): void
    {
        $this->assertResponseIsList('GET', '/api/movements', 200, 'MovementAttributes');
        $this->assertResponseIsList('GET', '/api/categories', 200, 'CategoryAttributes');
    }

    public function testTheResponseStatusIsTheOneTheControllerDeclares(): void
    {
        self::assertArrayNotHasKey('200', $this->operation('POST', '/api/movements')['responses']);
    }

    public function testTheResourceIdIsNotRepeatedInItsAttributes(): void
    {
        $this->assertSchemaHasNoProperty('MovementAttributes', 'id');
        $this->assertSchemaHasNoProperty('CategoryAttributes', 'id');
        $this->assertSchemaProperties(
            'MovementAttributes',
            ['title', 'description', 'category', 'area', 'location', 'status', 'createdAt', 'updatedAt'],
        );
    }

    public function testTheSignedInUserIsDocumentedAsAUsersResource(): void
    {
        $this->assertResponseIsResource('GET', '/api/me', 200, 'UserAttributes');
        $this->assertResponseIsResource('POST', '/api/session', 200, 'UserAttributes');
        $this->assertSchemaProperties('UserAttributes', ['email']);
    }

    public function testTheRealtimeTicketIsDocumentedAsARealtimeTicketsResource(): void
    {
        $this->assertResponseIsResource('POST', '/api/realtime-tickets', 201, 'RealtimeTicketAttributes');
        $this->assertSchemaProperties('RealtimeTicketAttributes', ['ticket', 'expiresAt', 'endpoint', 'channels']);
        $this->assertSchemaHasNoProperty('RealtimeTicketAttributes', 'id');
    }

    public function testWritesDocumentTheResourceDocumentTheyAccept(): void
    {
        self::assertSame('movements', $this->requestBody('POST', '/api/movements')['properties']['data']['properties']['type']['example']);
        self::assertSame('movements', $this->requestBody('PATCH', '/api/movements/{id}')['properties']['data']['properties']['type']['example']);
        self::assertSame('sessions', $this->requestBody('POST', '/api/session')['properties']['data']['properties']['type']['example']);
    }

    public function testEndpointsDocumentTheJsonApiErrorDocument(): void
    {
        foreach ([['GET', '/api/movements'], ['POST', '/api/session']] as [$method, $path]) {
            $schema = $this->operation($method, $path)['responses']['4XX']['content'][self::MEDIA_TYPE]['schema'] ?? null;

            self::assertSame('#/components/schemas/JsonApiErrors', $schema['$ref'] ?? null, "{$method} {$path}");
        }
    }

    public function testRequestAttributesAreTheFieldsOfTheCommandWithoutTheServerOwnedOnes(): void
    {
        $fields = ['title', 'description', 'category', 'area', 'location'];

        self::assertSame($fields, $this->requestAttributes('POST', '/api/movements'));
        self::assertSame($fields, $this->requestAttributes('PATCH', '/api/movements/{id}'));
        self::assertSame(['email'], $this->requestAttributes('POST', '/api/session'));
    }

    public function testTheSessionCookieIsTheDocumentsAuthentication(): void
    {
        self::assertSame([[
            'cookieAuth' => [],
        ]], $this->document['security'] ?? null);
        self::assertSame(
            [
                'type' => 'apiKey',
                'description' => 'The httpOnly `token` cookie set by `POST /api/session`. Sign in there first: the browser stores and sends the cookie itself, so the value field below is not used.',
                'name' => 'token',
                'in' => 'cookie',
            ],
            $this->document['components']['securitySchemes']['cookieAuth'] ?? null,
        );
        self::assertSame([], $this->operation('POST', '/api/session')['security'] ?? null);
        self::assertSame([], $this->operation('POST', '/api/logout')['security'] ?? null);
        self::assertArrayNotHasKey('security', $this->operation('GET', '/api/me'));
    }

    public function testSigningInAndOutDocumentTheCookieTheySet(): void
    {
        foreach ([['/api/session', '200'], ['/api/logout', '204']] as [$path, $status]) {
            $header = $this->operation('POST', $path)['responses'][$status]['headers']['Set-Cookie'] ?? null;

            self::assertIsArray($header, "POST {$path} documents no Set-Cookie header on its {$status} response.");
            self::assertNotEmpty($header['description'] ?? null);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function operation(string $method, string $path): array
    {
        $operation = $this->document['paths'][$path][strtolower($method)] ?? null;
        self::assertIsArray($operation, "{$method} {$path} is not in the OpenAPI document.");

        return $operation;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestBody(string $method, string $path): array
    {
        $schema = $this->operation($method, $path)['requestBody']['content'][self::MEDIA_TYPE]['schema'] ?? null;
        self::assertIsArray($schema, "{$method} {$path} has no ".self::MEDIA_TYPE.' request body.');

        return $schema;
    }

    /**
     * @return list<string>
     */
    private function requestAttributes(string $method, string $path): array
    {
        return array_map(strval(...), array_keys($this->requestBody($method, $path)['properties']['data']['properties']['attributes']['properties'] ?? []));
    }

    /**
     * @return array<string, mixed>
     */
    private function responseData(string $method, string $path, int $status): array
    {
        $schema = $this->operation($method, $path)['responses'][(string) $status]['content'][self::MEDIA_TYPE]['schema'] ?? null;
        self::assertIsArray($schema, "{$method} {$path} has no {$status} ".self::MEDIA_TYPE.' response.');
        self::assertIsArray($schema['properties']['data'] ?? null);

        return $schema['properties']['data'];
    }

    private function assertResponseIsResource(string $method, string $path, int $status, string $schema): void
    {
        $this->assertIsResourceOf($this->responseData($method, $path, $status), $schema);
    }

    private function assertResponseIsList(string $method, string $path, int $status, string $schema): void
    {
        $data = $this->responseData($method, $path, $status);

        self::assertSame('array', $data['type'] ?? null);
        self::assertIsArray($data['items'] ?? null);
        $this->assertIsResourceOf($data['items'], $schema);
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function assertIsResourceOf(array $resource, string $schema): void
    {
        self::assertSame(['id', 'type', 'attributes'], $resource['required'] ?? null);
        self::assertSame('#/components/schemas/'.$schema, $resource['properties']['attributes']['$ref'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(string $name): array
    {
        $schema = $this->document['components']['schemas'][$name] ?? null;
        self::assertIsArray($schema, "Schema \"{$name}\" is not in the OpenAPI document.");

        return $schema;
    }

    /**
     * @param list<string> $properties
     */
    private function assertSchemaProperties(string $schema, array $properties): void
    {
        self::assertSame($properties, array_keys($this->schema($schema)['properties'] ?? []));
    }

    private function assertSchemaHasNoProperty(string $schema, string $property): void
    {
        $definition = $this->schema($schema);

        self::assertArrayNotHasKey($property, $definition['properties'] ?? []);
        self::assertNotContains($property, $definition['required'] ?? []);
    }
}
