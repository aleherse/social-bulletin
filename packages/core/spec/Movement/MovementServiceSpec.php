<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Movement;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Helper\IdentityGenerator;
use SocialBulletin\Core\Movement\DraftMovement;
use SocialBulletin\Core\Movement\EditMovement;
use SocialBulletin\Core\Movement\InvalidMovement;
use SocialBulletin\Core\Movement\Movement;
use SocialBulletin\Core\Movement\MovementNotDraft;
use SocialBulletin\Core\Movement\MovementNotFound;
use SocialBulletin\Core\Movement\MovementRepository;
use SocialBulletin\Core\Movement\MovementStatus;
use SocialBulletin\Core\Movement\SubmitMovement;

final class MovementServiceSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $this->beConstructedWith($movements, $identities);
    }

    public function it_creates_a_draft_movement(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::that(
            static fn (Movement $movement): bool => self::ID === $movement->id
                && self::AUTHOR_ID === $movement->authorId
                && MovementStatus::Draft === $movement->status(),
        ))->will(self::echoesBackTheSavedMovement())
            ->shouldBeCalled();

        $movement = $this->create($this->draftCommand());

        $movement->title()->shouldBe('Community Gardens for Everyone');
        $movement->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_creates_a_draft_with_an_empty_description(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::type(Movement::class))
            ->will(self::echoesBackTheSavedMovement())
            ->shouldBeCalled();

        $movement = $this->create($this->draftCommand(description: ''));

        $movement->description()->shouldBe('');
    }

    public function it_collects_an_error_for_every_invalid_field(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)->during('create', [
            $this->draftCommand(title: '   ', description: '', category: '', area: 'galaxy', location: null),
        ]);
    }

    public function it_requires_a_location_for_local_areas(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)->during('create', [
            $this->draftCommand(description: '', location: null),
        ]);
    }

    public function it_rejects_a_location_on_an_international_movement(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)->during('create', [
            $this->draftCommand(title: 'Global Climate Strike', description: '', area: 'international'),
        ]);
    }

    public function it_lists_the_authors_movements(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movements->byAuthor(self::AUTHOR_ID)->willReturn([$movement]);

        $this->byAuthor(self::AUTHOR_ID)->shouldBe([$movement]);
    }

    public function it_returns_the_authors_movement(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movements->byId(self::ID)->willReturn($movement);

        $this->authorMovement(self::ID, self::AUTHOR_ID)->shouldBe($movement);
    }

    public function it_hides_movements_that_belong_to_another_user(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movements->byId(self::ID)->willReturn($movement);

        $this->shouldThrow(MovementNotFound::class)
            ->during('authorMovement', [self::ID, '0198f2f0-6d2c-7cf0-a2b8-333333333333']);
    }

    public function it_reports_an_unknown_movement_as_not_found(
        MovementRepository $movements,
    ): void {
        $movements->byId(self::ID)->willReturn(null);

        $this->shouldThrow(MovementNotFound::class)
            ->during('authorMovement', [self::ID, self::AUTHOR_ID]);
    }

    public function it_submits_the_authors_described_draft(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save($movement)->willReturn($movement)
            ->shouldBeCalled();

        $this->submit(self::ID, self::AUTHOR_ID)->status()->shouldBe(MovementStatus::Proposed);
    }

    public function it_refuses_to_submit_a_draft_without_a_description(
        MovementRepository $movements,
    ): void {
        $movement = Movement::draft(self::ID, $this->draftCommand(description: ''));
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)
            ->during('submit', [self::ID, self::AUTHOR_ID]);
    }

    public function it_conflicts_when_submitting_a_movement_that_is_not_a_draft(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movement->apply(new SubmitMovement());
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotDraft::class)
            ->during('submit', [self::ID, self::AUTHOR_ID]);
    }

    public function it_updates_the_authors_draft(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save($movement)->willReturn($movement)
            ->shouldBeCalled();

        $updated = $this->update(self::ID, self::AUTHOR_ID, new EditMovement(
            'Save All the Bees',
            'New description.',
            'animal_rights',
            'region',
            'Yorkshire',
        ));

        $updated->title()->shouldBe('Save All the Bees');
        $updated->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_conflicts_when_editing_a_movement_that_is_not_a_draft(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movement->apply(new SubmitMovement());
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotDraft::class)->during('update', [
            self::ID,
            self::AUTHOR_ID,
            new EditMovement(
                'Save All the Bees',
                'New description.',
                'cooperative',
                'municipality',
                'Sheffield',
            ),
        ]);
    }

    /**
     * The repository returns the stored row as a fresh aggregate; for these examples the
     * movement handed to `save()` stands in for it.
     *
     * @return callable(array<int, mixed>): Movement
     */
    private static function echoesBackTheSavedMovement(): callable
    {
        return static function (array $arguments): Movement {
            $movement = $arguments[0];
            \assert($movement instanceof Movement);

            return $movement;
        };
    }

    private function describedDraft(): Movement
    {
        return Movement::draft(self::ID, $this->draftCommand());
    }

    // `string|null` rather than `?string`: PhpSpec's spec loader rejects `?type` parameters.
    private function draftCommand(
        string $title = 'Community Gardens for Everyone',
        string $description = "## Why\nGardens for all.",
        string $category = 'cooperative',
        string $area = 'municipality',
        string|null $location = 'Sheffield',
    ): DraftMovement {
        return new DraftMovement(
            self::AUTHOR_ID,
            $title,
            $description,
            $category,
            $area,
            $location,
        );
    }
}
