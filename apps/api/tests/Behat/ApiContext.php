<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Behat;

use Behat\Behat\Context\Context;
use SocialBulletin\Api\Persistence\DbalUserRepository;
use SocialBulletin\Core\User\EmailAddress;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Webmozart\Assert\Assert;

final class ApiContext implements Context
{
    public function __construct(
        private readonly KernelBrowser $client,
        private readonly DbalUserRepository $users,
        private readonly JmespathContext $jmespathContext,
    ) {
    }

    /** @Given no baseline fixture data is required */
    public function noBaselineFixtureDataIsRequired(): void
    {
    }

    /** @Given a user exists with email :email */
    public function aUserExistsWithEmail(string $email): void
    {
        $emailAddress = new EmailAddress($email);

        if ($this->users->findByEmail($emailAddress) === null) {
            $this->users->create($emailAddress);
        }
    }

    /** @When I submit registration email :email */
    public function iSubmitRegistrationEmail(string $email): void
    {
        $this->client->request(
            'POST',
            '/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email], JSON_THROW_ON_ERROR),
        );

        $this->syncJsonResponse();
    }

    /** @When I request the current authenticated user */
    public function iRequestTheCurrentAuthenticatedUser(): void
    {
        $this->client->request('GET', '/auth/me');

        $this->syncJsonResponse();
    }

    /** @Then the response status code should be :statusCode */
    public function theResponseStatusCodeShouldBe(int $statusCode): void
    {
        Assert::same($this->client->getResponse()->getStatusCode(), $statusCode);
    }

    /** @Then the response should set a secure httpOnly :name cookie */
    public function theResponseShouldSetASecureHttpOnlyCookie(string $name): void
    {
        $cookie = $this->client->getResponse()->headers->get('Set-Cookie');

        Assert::notNull($cookie);
        Assert::startsWith($cookie, $name . '=');
        Assert::contains($cookie, 'secure');
        Assert::contains($cookie, 'httponly');
        Assert::contains(strtolower($cookie), 'samesite=strict');
        Assert::contains($cookie, 'path=/');
    }

    private function syncJsonResponse(): void
    {
        $content = $this->client->getResponse()->getContent();

        Assert::string($content);
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException($content, previous: $exception);
        }

        $this->jmespathContext->setResponseData($data);
    }
}
