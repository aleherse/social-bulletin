<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Behat;

use Behat\Behat\Context\Context;
use Doctrine\DBAL\Connection;
use RuntimeException;
use SocialBulletin\Api\Kernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class FeatureContext implements Context
{
    private KernelBrowser $client;
    private ?Response $response = null;
    private static ?Kernel $sharedKernel = null;

    public function __construct()
    {
        if (self::$sharedKernel === null) {
            self::$sharedKernel = new Kernel('test', true);
            self::$sharedKernel->boot();
        }

        $this->client = new KernelBrowser(self::$sharedKernel);
        $this->client->disableReboot();
    }

    /**
     * @Given the database is clean
     */
    public function theDatabaseIsClean(): void
    {
        $this->getConnection()->executeStatement('DELETE FROM api_users');
    }

    /**
     * @Given a user with email :email already exists
     */
    public function aUserWithEmailAlreadyExists(string $email): void
    {
        $this->theDatabaseIsClean();
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'         => $email,
                'password'      => 'Str0ng!P@ssw0rd',
                'termsAccepted' => true,
            ], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @Given I have registered as :email with password :password
     */
    public function iHaveRegisteredAs(string $email, string $password): void
    {
        $this->theDatabaseIsClean();
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'         => $email,
                'password'      => $password,
                'termsAccepted' => true,
            ], JSON_THROW_ON_ERROR)
        );

        $status = $this->client->getResponse()->getStatusCode();
        if ($status !== 201) {
            throw new RuntimeException(sprintf('Registration failed with status %d.', $status));
        }
    }

    /**
     * @When I request :path
     */
    public function iRequest(string $path): void
    {
        $this->client->request('GET', $path);
        $this->response = $this->client->getResponse();
    }

    /**
     * @When I send a POST request to :path with JSON:
     */
    public function iSendAPostRequestToWithJson(string $path, string $body): void
    {
        $this->client->request(
            'POST',
            $path,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        );
        $this->response = $this->client->getResponse();
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(int $statusCode): void
    {
        $response = $this->requireResponse();

        if ($response->getStatusCode() !== $statusCode) {
            throw new RuntimeException(sprintf(
                'Expected status code %d, got %d. Body: %s',
                $statusCode,
                $response->getStatusCode(),
                $response->getContent()
            ));
        }
    }

    /**
     * @Then the JSON response should contain :key with :value
     */
    public function theJsonResponseShouldContainWith(string $key, string $value): void
    {
        $payload = json_decode($this->requireResponse()->getContent(), true);

        if (!is_array($payload) || ($payload[$key] ?? null) !== $value) {
            throw new RuntimeException(sprintf('Expected JSON key "%s" to contain "%s".', $key, $value));
        }
    }

    /**
     * @Then the JSON response should contain key :key
     */
    public function theJsonResponseShouldContainKey(string $key): void
    {
        $payload = json_decode($this->requireResponse()->getContent(), true);

        if (!is_array($payload) || !array_key_exists($key, $payload)) {
            throw new RuntimeException(sprintf('Expected JSON key "%s" to exist. Body: %s', $key, $this->requireResponse()->getContent()));
        }
    }

    /**
     * @Then the response should set an httpOnly cookie named :cookieName
     */
    public function theResponseShouldSetAnHttpOnlyCookieNamed(string $cookieName): void
    {
        $cookies = $this->requireResponse()->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $cookieName && $cookie->isHttpOnly()) {
                return;
            }
        }

        throw new RuntimeException(sprintf('Expected httpOnly cookie "%s" not found in response.', $cookieName));
    }

    /**
     * @Then the JSON response should contain a field error for :field
     */
    public function theJsonResponseShouldContainAFieldErrorFor(string $field): void
    {
        $payload = json_decode($this->requireResponse()->getContent(), true);

        if (!is_array($payload) || !isset($payload['errors']) || !is_array($payload['errors'])) {
            throw new RuntimeException(sprintf('Expected "errors" array in JSON. Body: %s', $this->requireResponse()->getContent()));
        }

        foreach ($payload['errors'] as $error) {
            if (($error['field'] ?? null) === $field) {
                return;
            }
        }

        throw new RuntimeException(sprintf('Expected field error for "%s". Errors: %s', $field, json_encode($payload['errors'])));
    }

    private function requireResponse(): Response
    {
        if (!$this->response instanceof Response) {
            throw new RuntimeException('No response has been received.');
        }

        return $this->response;
    }

    private function getConnection(): Connection
    {
        $kernel = self::$sharedKernel ?? throw new RuntimeException('Kernel not initialised.');

        return $kernel->getContainer()->get('doctrine.dbal.default_connection');
    }
}
