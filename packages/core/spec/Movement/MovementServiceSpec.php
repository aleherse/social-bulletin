<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Movement;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Helper\IdentityGenerator;
use SocialBulletin\Core\Movement\InvalidMovement;
use SocialBulletin\Core\Movement\Movement;
use SocialBulletin\Core\Movement\MovementNotDraft;
use SocialBulletin\Core\Movement\MovementNotFound;
use SocialBulletin\Core\Movement\MovementRepository;
use SocialBulletin\Core\Movement\MovementStatus;

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
        ))->shouldBeCalled();

        $movement = $this->create(
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            "## Why\nGardens for all.",
            'cooperative',
            'municipality',
            'Sheffield',
        );

        $movement->title()->shouldBe('Community Gardens for Everyone');
        $movement->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_creates_a_draft_with_an_empty_description(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::type(Movement::class))->shouldBeCalled();

        $movement = $this->create(
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            'municipality',
            'Sheffield',
        );

        $movement->description()->shouldBe('');
    }

    public function it_collects_an_error_for_every_invalid_field(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)
            ->during('create', [self::AUTHOR_ID, '   ', '', 'unknown', 'galaxy', null]);
    }

    public function it_requires_a_location_for_local_areas(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)->during(
            'create',
            [self::AUTHOR_ID, 'Community Gardens for Everyone', '', 'cooperative', 'municipality', null],
        );
    }

    public function it_rejects_a_location_on_an_international_movement(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)->during(
            'create',
            [self::AUTHOR_ID, 'Global Climate Strike', '', 'cooperative', 'international', 'Sheffield'],
        );
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
        $movements->save($movement)->shouldBeCalled();

        $this->submit(self::ID, self::AUTHOR_ID)->status()->shouldBe(MovementStatus::Proposed);
    }

    public function it_refuses_to_submit_a_draft_without_a_description(
        MovementRepository $movements,
    ): void {
        $movement = Movement::draft(
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            'municipality',
            'Sheffield',
            new \DateTimeImmutable(),
        );
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)
            ->during('submit', [self::ID, self::AUTHOR_ID]);
    }

    public function it_conflicts_when_submitting_a_movement_that_is_not_a_draft(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movement->submit(new \DateTimeImmutable());
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
        $movements->save($movement)->shouldBeCalled();

        $updated = $this->update(
            self::ID,
            self::AUTHOR_ID,
            'Save All the Bees',
            'New description.',
            'animal_rights',
            'region',
            'Yorkshire',
        );

        $updated->title()->shouldBe('Save All the Bees');
        $updated->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_conflicts_when_editing_a_movement_that_is_not_a_draft(
        MovementRepository $movements,
    ): void {
        $movement = $this->describedDraft();
        $movement->submit(new \DateTimeImmutable());
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotDraft::class)->during('update', [
            self::ID,
            self::AUTHOR_ID,
            'Save All the Bees',
            'New description.',
            'cooperative',
            'municipality',
            'Sheffield',
        ]);
    }

    private function describedDraft(): Movement
    {
        return Movement::draft(
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            "## Why\nGardens for all.",
            'cooperative',
            'municipality',
            'Sheffield',
            new \DateTimeImmutable(),
        );
    }
}
