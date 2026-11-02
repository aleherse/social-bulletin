<?php

declare(strict_types=1);

namespace App\Tests\Integration\Movement;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Support\RecordingEventDispatcher;
use SocialBulletin\Core\Domain\Movement\Events\MovementHasBeenCreated;
use SocialBulletin\Core\Domain\Movement\Events\MovementHasBeenUpdated;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementStatus;
use SocialBulletin\Core\Domain\User\UserId;

final class MovementRepositoryTest extends IntegrationTestCase
{
    private MovementRepository $movements;

    private RecordingEventDispatcher $events;

    private UserId $authorId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movements = self::service(MovementRepository::class);
        $this->events = self::service(RecordingEventDispatcher::class);
        $this->authorId = $this->signIn('author@example.com');
    }

    public function testItStoresADraft(): void
    {
        $draft = $this->draft();

        $this->movements->save($draft);

        $stored = $this->movements->authorMovement((string) $draft->id, $this->authorId);
        self::assertSame('Community Gardens for Everyone', $stored->title());
        self::assertSame(MovementStatus::Draft, $stored->status());
    }

    public function testSavingAgainUpdatesTheRowAndMovesUpdatedAtPastCreatedAt(): void
    {
        $draft = $this->draft();
        $this->movements->save($draft);

        $stored = $this->movements->authorMovement((string) $draft->id, $this->authorId);
        $stored->edit('Save All the Bees', '', 'cooperative', 'municipality', 'Sheffield');
        $this->movements->save($stored);

        $updated = $this->movements->authorMovement((string) $draft->id, $this->authorId);
        self::assertSame('Save All the Bees', $updated->title());
        self::assertGreaterThan($updated->createdAt(), $updated->updatedAt());
    }

    public function testItRejectsACategoryOutsideTheManagedList(): void
    {
        try {
            $this->movements->save($this->draft(category: 'not-a-real-category'));
            self::fail('Expected InvalidMovement.');
        } catch (InvalidMovement $exception) {
            self::assertSame([
                'category' => 'movement.category.unknown',
            ], $exception->errors);
        }
    }

    public function testItPublishesTheCreationOnceTheRowIsStored(): void
    {
        $draft = $this->draft();

        $this->movements->save($draft);

        $dispatched = $this->events->dispatched();
        self::assertCount(1, $dispatched);
        self::assertInstanceOf(MovementHasBeenCreated::class, $dispatched[0]);
        self::assertTrue($this->events->wasStoredBeforeDispatch((string) $draft->id));
    }

    public function testItPublishesNothingWhenTheWriteFails(): void
    {
        try {
            $this->movements->save($this->draft(category: 'not-a-real-category'));
        } catch (InvalidMovement) {
        }

        self::assertSame([], $this->events->dispatched());
    }

    public function testItPublishesTheUpdateButNotTheCreationAgainWhenAStoredMovementIsEdited(): void
    {
        $draft = $this->draft();
        $this->movements->save($draft);

        $stored = $this->movements->authorMovement((string) $draft->id, $this->authorId);
        $stored->edit('Save All the Bees', '', 'cooperative', 'municipality', 'Sheffield');
        $this->movements->save($stored);

        $dispatched = $this->events->dispatched();
        self::assertCount(2, $dispatched);
        self::assertInstanceOf(MovementHasBeenCreated::class, $dispatched[0]);
        self::assertInstanceOf(MovementHasBeenUpdated::class, $dispatched[1]);
    }

    private function draft(string $category = 'cooperative'): Movement
    {
        return Movement::draft(
            MovementId::generate(),
            $this->authorId,
            'Community Gardens for Everyone',
            '',
            $category,
            'municipality',
            'Sheffield',
        );
    }
}
