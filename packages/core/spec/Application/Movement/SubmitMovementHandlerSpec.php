<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\Movement\SubmitMovementCommand;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementService;
use SocialBulletin\Core\Domain\Movement\MovementStatus;
use SocialBulletin\Core\Domain\User\UserId;

final class SubmitMovementHandlerSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    // `MovementService` is final, so the real service runs on top of the doubled repository.
    public function let(MovementRepository $movements): void
    {
        $this->beConstructedWith(
            new MovementService($movements->getWrappedObject()),
            $movements,
        );
    }

    public function it_saves_the_described_draft_as_proposed(
        MovementRepository $movements,
    ): void {
        $movement = self::draftWith("## Why\nGardens for all.");
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save($movement)->willReturn($movement)
            ->shouldBeCalled();

        $this->__invoke($this->command())
            ->status()
            ->shouldBe(MovementStatus::Proposed);
    }

    public function it_saves_nothing_when_the_draft_has_no_description(
        MovementRepository $movements,
    ): void {
        $movements->byId(self::ID)->willReturn(self::draftWith(''));
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)->during('__invoke', [$this->command()]);
    }

    public function it_refuses_to_submit_a_movement_that_left_draft(
        MovementRepository $movements,
    ): void {
        $movement = self::draftWith("## Why\nGardens for all.");
        $movement->submit();
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotDraft::class)->during('__invoke', [$this->command()]);
    }

    public function it_hides_a_movement_that_belongs_to_another_author(
        MovementRepository $movements,
    ): void {
        $movements->byId(self::ID)->willReturn(self::draftWith("## Why\nGardens for all."));
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotFound::class)->during('__invoke', [
            $this->command(UserId::from('0198f2f0-6d2c-7cf0-a2b8-333333333333')),
        ]);
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
