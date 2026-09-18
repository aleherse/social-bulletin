<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Doctrine\DBAL\Connection;
use App\Messenger\CommandBus;
use SocialBulletin\Core\Application\Movement\SubmitMovementCommand;
use SocialBulletin\Core\Application\Movement\CreateMovementCommand;
use SocialBulletin\Core\Application\User\SignInCommand;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\User\User;
use Webmozart\Assert\Assert;

use function JmesPath\search;

final class MovementContext implements Context
{
    /** @var array<string, string> Movement ids created by Given steps, keyed by title. */
    private array $movementIds = [];

    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly CommandBus $commandBus,
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
        $user = $this->signIn($email);
        $this->createMovement($email, $title, "## Why\nBecause it matters.");
        $this->commandBus->dispatch(new SubmitMovementCommand($this->movementId($title), $user->id));
    }

    #[Given(':email has a movement draft with:')]
    public function hasAMovementDraftWith(string $email, TableNode $table): void
    {
        $fields = $table->getRowsHash();
        Assert::keyExists($fields, 'title', 'The movement draft table must have a "title" row.');

        $this->createMovement(
            $email,
            $fields['title'],
            $fields['description'] ?? '',
            $fields['category'] ?? 'cooperative',
            $fields['area'] ?? 'municipality',
            $fields['location'] ?? 'Sheffield',
        );
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

    /**
     * Both a movement's author and the caller acting on it start as a sign-in.
     */
    private function signIn(string $email): User
    {
        $user = $this->commandBus->dispatch(SignInCommand::fromPayload(['email' => $email]));
        Assert::isInstanceOf($user, User::class);

        return $user;
    }

    private function createMovement(
        string $email,
        string $title,
        string $description,
        string $category = 'cooperative',
        string $area = 'municipality',
        string $location = 'Sheffield',
    ): void {
        $user = $this->signIn($email);
        $movement = $this->commandBus->dispatch(CreateMovementCommand::fromPayload([
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'area' => $area,
            'location' => $location,
        ], $user->id));
        Assert::isInstanceOf($movement, Movement::class);

        $this->movementIds[$title] = (string) $movement->id;
    }

    /**
     * Falls back to the database for movements seeded by the baseline fixture rather than by a
     * Given step in the current scenario.
     */
    private function movementId(string $title): string
    {
        if (!isset($this->movementIds[$title])) {
            $id = $this->connection->fetchOne(
                'SELECT id FROM bulletin.movements WHERE title = :title',
                ['title' => $title],
            );
            Assert::string($id, sprintf('No movement titled "%s" exists.', $title));

            $this->movementIds[$title] = $id;
        }

        return $this->movementIds[$title];
    }
}
