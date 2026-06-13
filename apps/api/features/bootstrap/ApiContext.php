<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Features\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Doctrine\DBAL\Connection;
use JmesPath\Env as JmesPath;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use SocialBulletin\Api\Security\AuthenticatedUser;
use SocialBulletin\Core\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class ApiContext implements Context
{
    private ?Response $response = null;
    private ?string $token = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Connection $connection,
        private readonly JWTTokenManagerInterface $jwt,
    ) {
    }

    /** @BeforeScenario */
    public function restoreSnapshot(BeforeScenarioScope $scope): void
    {
        if ($scope->getScenario()->hasTag('fixtures')) {
            return;
        }

        exec('dslr restore fixtures >/dev/null 2>&1');
        $this->token = null;
        $this->response = null;
    }

    /** @Given a user exists with email :email */
    public function userExistsWithEmail(string $email): void
    {
        $this->connection->executeStatement('CREATE SCHEMA IF NOT EXISTS bulletin');
        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS bulletin.users (id UUID NOT NULL PRIMARY KEY, email VARCHAR(254) NOT NULL UNIQUE, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP)',
        );
        $this->connection->executeStatement(
            'INSERT INTO bulletin.users (id, email) VALUES (:id, :email) ON CONFLICT (email) DO NOTHING',
            ['id' => '018ff6f2-3a17-7b7e-94e3-0b3cdb6d1000', 'email' => strtolower($email)],
        );
    }

    /** @Given I am authenticated as :email */
    public function iAmAuthenticatedAs(string $email): void
    {
        $this->userExistsWithEmail($email);
        $user = new User('018ff6f2-3a17-7b7e-94e3-0b3cdb6d1000', strtolower($email));
        $this->token = $this->jwt->createFromPayload(new AuthenticatedUser($user), ['email' => $user->email]);
    }

    /** @When I submit the registration email :email */
    public function iSubmitTheRegistrationEmail(string $email): void
    {
        $this->request('POST', '/api/auth/register-or-login', ['email' => $email]);
    }

    /** @When I request the current user */
    public function iRequestTheCurrentUser(): void
    {
        $this->request('GET', '/api/me');
    }

    /** @When I log out */
    public function iLogOut(): void
    {
        $this->request('POST', '/api/auth/logout');
    }

    /** @Then the response status code should be :status */
    public function theResponseStatusCodeShouldBe(int $status): void
    {
        $actual = $this->lastResponse()->getStatusCode();
        if ($actual !== $status) {
            throw new \RuntimeException(sprintf('Expected status %d, got %d.', $status, $actual));
        }
    }

    /** @Then the JSON response at :path should be :expected */
    public function theJsonResponseAtShouldBe(string $path, string $expected): void
    {
        $payload = json_decode($this->lastResponse()->getContent() ?: '{}', true, flags: JSON_THROW_ON_ERROR);
        $actual = JmesPath::search($path, $payload);
        if ($actual !== $expected) {
            throw new \RuntimeException(sprintf('Expected JSON path %s to be %s, got %s.', $path, $expected, json_encode($actual)));
        }
    }

    /** @Then the response should set an httpOnly token cookie */
    public function theResponseShouldSetAnHttpOnlyTokenCookie(): void
    {
        foreach ($this->lastResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'token' && $cookie->isHttpOnly()) {
                return;
            }
        }

        throw new \RuntimeException('Response did not set an httpOnly token cookie.');
    }

    /** @Then the response should clear the token cookie */
    public function theResponseShouldClearTheTokenCookie(): void
    {
        foreach ($this->lastResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'token' && $cookie->getExpiresTime() <= time()) {
                return;
            }
        }

        throw new \RuntimeException('Response did not clear the token cookie.');
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $method, string $path, array $body = []): void
    {
        $cookies = $this->token === null ? [] : ['token' => $this->token];
        $content = $body === [] ? null : json_encode($body, JSON_THROW_ON_ERROR);
        $this->response = $this->kernel->handle(Request::create($path, $method, [], $cookies, [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $content));
    }

    private function lastResponse(): Response
    {
        if (!$this->response instanceof Response) {
            throw new \RuntimeException('No response has been received.');
        }

        return $this->response;
    }
}
