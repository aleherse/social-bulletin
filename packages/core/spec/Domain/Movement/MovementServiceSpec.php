<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Domain\Movement;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementRepository;

final class MovementServiceSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(MovementRepository $movements): void
    {
        $this->beConstructedWith($movements);
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
        );
    }
}
