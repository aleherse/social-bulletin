<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Doctrine\DBAL\Connection;
use SocialBulletin\Core\UserService;
use Webmozart\Assert\Assert;

use function JmesPath\search;

final class SessionContext implements Context
{
    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly UserService $userService,
        private readonly Connection $connection,
    ) {
    }

    #[Given('a user exists with email :email')]
    public function aUserExistsWithEmail(string $email): void
    {
        // ADR-0015: Given steps create state through application code.
        $this->userService->findOrCreateByEmail($email);
    }

    #[When('I send a :method request to :path')]
    public function iSendARequestTo(string $method, string $path): void
    {
        $this->apiClient->request($method, $path);
    }

    #[When('I send a :method request to :path with body:')]
    public function iSendARequestToWithBody(string $method, string $path, PyStringNode $body): void
    {
        $this->apiClient->request($method, $path, $body->getRaw());
    }

    #[Then('the response status code should be :code')]
    public function theResponseStatusCodeShouldBe(int $code): void
    {
        Assert::same($this->apiClient->response()->getStatusCode(), $code);
    }

    #[Then('the JSON at :expression should equal :value')]
    public function theJsonAtShouldEqual(string $expression, string $value): void
    {
        Assert::same(search($expression, $this->apiClient->decodedResponse()), $value);
    }

    #[Then('the JSON at :expression should not be empty')]
    public function theJsonAtShouldNotBeEmpty(string $expression): void
    {
        $result = search($expression, $this->apiClient->decodedResponse());

        Assert::string($result);
        Assert::stringNotEmpty(trim($result));
    }

    #[Then('the response should set an httpOnly cookie named :name')]
    public function theResponseShouldSetAnHttpOnlyCookieNamed(string $name): void
    {
        $cookie = $this->apiClient->findResponseCookie($name);

        Assert::notNull($cookie, sprintf('No "%s" cookie was set on the response.', $name));
        Assert::stringNotEmpty((string) $cookie->getValue());
        Assert::true($cookie->isHttpOnly(), sprintf('The "%s" cookie is not httpOnly.', $name));
        Assert::true($cookie->isSecure(), sprintf('The "%s" cookie is not Secure.', $name));
    }

    #[Then('the response should not set a cookie named :name')]
    public function theResponseShouldNotSetACookieNamed(string $name): void
    {
        Assert::null(
            $this->apiClient->findResponseCookie($name),
            sprintf('The response unexpectedly set a "%s" cookie.', $name),
        );
    }

    #[Then('exactly one user should exist with email :email')]
    public function exactlyOneUserShouldExistWithEmail(string $email): void
    {
        Assert::same($this->countUsersWithEmail($email), 1);
    }

    #[Then('no user should exist with email :email')]
    public function noUserShouldExistWithEmail(string $email): void
    {
        Assert::same($this->countUsersWithEmail($email), 0);
    }

    private function countUsersWithEmail(string $email): int
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM bulletin.users WHERE LOWER(email) = LOWER(:email)',
            ['email' => $email],
        );

        return (int) $count;
    }
}
