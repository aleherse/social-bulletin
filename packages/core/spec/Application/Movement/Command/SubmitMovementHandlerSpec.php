<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement\Command;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\Movement\Command\SubmitMovementCommand;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementStatus;
use SocialBulletin\Core\Domain\User\UserId;

final class SubmitMovementHandlerSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(MovementRepository $movements): void
    {
        $this->beConstructedWith($movements);
    }

    public function it_saves_the_described_draft_as_proposed(
        MovementRepository $movements,
    ): void {
        $movement = self::draftWith("## Why\nGardens for all.");
        $movements->authorMovement(self::ID, Argument::any())->willReturn($movement);
        $movements->save(Argument::that(
            static fn (Movement $saved): bool => MovementStatus::Proposed === $saved->status(),
        ))->shouldBeCalled();

        $this->__invoke($this->command());
    }

    public function it_saves_nothing_when_the_draft_has_no_description(
        MovementRepository $movements,
    ): void {
        $movements->authorMovement(self::ID, Argument::any())->willReturn(self::draftWith(''));
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)->during('__invoke', [$this->command()]);
    }

    public function it_refuses_to_submit_a_movement_that_left_draft(
        MovementRepository $movements,
    ): void {
        $movement = self::draftWith("## Why\nGardens for all.");
        $movement->submit();
        $movements->authorMovement(self::ID, Argument::any())->willReturn($movement);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotDraft::class)->during('__invoke', [$this->command()]);
    }

    public function it_saves_nothing_when_the_repository_hides_the_movement(
        MovementRepository $movements,
    ): void {
        $movements->authorMovement(self::ID, Argument::any())
            ->willThrow(new MovementNotFound('movement.not_found'));
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotFound::class)->during('__invoke', [$this->command()]);
    }

    private static function draftWith(string $description): Movement
    {
        return Movement::draft(
            MovementId::from(self::ID),
            UserId::from(self::AUTHOR_ID),
            'Community Gardens for Everyone',
            $description,
            'cooperative',
            'municipality',
            'Sheffield',
        );
    }

    private function command(UserId|null $authorId = null): SubmitMovementCommand
    {
        return new SubmitMovementCommand(self::ID, $authorId ?? UserId::from(self::AUTHOR_ID));
    }
}
