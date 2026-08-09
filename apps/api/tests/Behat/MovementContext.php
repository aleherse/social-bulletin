<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Doctrine\DBAL\Connection;
use SocialBulletin\Core\Movement\DraftMovement;
use SocialBulletin\Core\Movement\MovementService;
use SocialBulletin\Core\User\UserService;
use Webmozart\Assert\Assert;

use function JmesPath\search;

final class MovementContext implements Context
{
    /** @var array<string, string> Movement ids created by Given steps, keyed by title. */
    private array $movementIds = [];

    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly UserService $userService,
        private readonly MovementService $movementService,
        private readonly Connection $connection,
    ) {
    }

    #[Given(':email has a movement draft titled :title')]
    public function hasAMovementDraftTitled(string $email, string $title): void
    {
        // ADR-0015: Given steps create state through application code.
        $this->createMovement($email, $title, '');
    }

    #[Given(':email has a movement draft titled :title with a description')]
    public function hasAMovementDraftTitledWithADescription(string $email, string $title): void
    {
        $this->createMovement($email, $title, "## Why\nBecause it matters.");
    }

    #[Given(':email has a proposed movement titled :title')]
    public function hasAProposedMovementTitled(string $email, string $title): void
    {
        $user = $this->userService->findOrCreateByEmail($email);
        $this->createMovement($email, $title, "## Why\nBecause it matters.");
        $this->movementService->submit($this->movementId($title), $user->id);
    }

    #[When('I send a :method request to the movement titled :title')]
    public function iSendARequestToTheMovementTitled(string $method, string $title): void
    {
        $this->apiClient->request($method, sprintf('/api/movements/%s', $this->movementId($title)));
    }

    #[When('I send a :method request to the movement titled :title with body:')]
    public function iSendARequestToTheMovementTitledWithBody(
        string $method,
        string $title,
        PyStringNode $body,
    ): void {
        $this->apiClient->request(
            $method,
            sprintf('/api/movements/%s', $this->movementId($title)),
            $body->getRaw(),
        );
    }

    #[When('I submit the movement titled :title')]
    public function iSubmitTheMovementTitled(string $title): void
    {
        $this->apiClient->request(
            'POST',
            sprintf('/api/movements/%s/submit', $this->movementId($title)),
        );
    }

    #[Then('the movement titled :title should have been updated after it was created')]
    public function theMovementTitledShouldHaveBeenUpdatedAfterItWasCreated(string $title): void
    {
        /** @var array<string, string>|false $row */
        $row = $this->connection->fetchAssociative(
            'SELECT created_at, updated_at FROM bulletin.movements WHERE id = :id',
            [
                'id' => $this->movementId($title),
            ],
        );

        Assert::isArray($row);
        Assert::greaterThan(
            new \DateTimeImmutable($row['updated_at']),
            new \DateTimeImmutable($row['created_at']),
        );
    }

    #[Then('the JSON at :expression should be null')]
    public function theJsonAtShouldBeNull(string $expression): void
    {
        Assert::null(search($expression, $this->apiClient->decodedResponse()));
    }

    #[Then('the JSON at :expression should have :count items')]
    public function theJsonAtShouldHaveItems(string $expression, int $count): void
    {
        $result = search($expression, $this->apiClient->decodedResponse());

        Assert::isArray($result);
        Assert::count($result, $count);
    }

    private function createMovement(string $email, string $title, string $description): void
    {
        $user = $this->userService->findOrCreateByEmail($email);
        $movement = $this->movementService->create(new DraftMovement(
            $user->id,
            $title,
            $description,
            'cooperative',
            'municipality',
            'Sheffield',
        ));

        $this->movementIds[$title] = $movement->id;
    }

    private function movementId(string $title): string
    {
        Assert::keyExists(
            $this->movementIds,
            $title,
            sprintf('No movement titled "%s" was created by a Given step.', $title),
        );

        return $this->movementIds[$title];
    }
}
