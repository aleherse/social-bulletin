<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\Movement\CreateMovementCommand;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementStatus;
use SocialBulletin\Core\Domain\User\UserId;

final class CreateMovementHandlerSpec extends ObjectBehavior
{
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(MovementRepository $movements): void
    {
        $this->beConstructedWith($movements);
    }

    public function it_saves_a_new_draft_under_the_identity_the_command_minted(
        MovementRepository $movements,
    ): void {
        $command = $this->draftCommand();

        $movements->save(Argument::that(
            static fn (Movement $movement): bool => (string) $command->id === (string) $movement->id
                && self::AUTHOR_ID === (string) $movement->authorId
                && 'Community Gardens for Everyone' === $movement->title()
                && MovementStatus::Draft === $movement->status(),
        ))->shouldBeCalled();

        $this->__invoke($command);
    }

    public function it_saves_a_draft_with_an_empty_description(
        MovementRepository $movements,
    ): void {
        $movements->save(Argument::that(
            static fn (Movement $movement): bool => '' === $movement->description(),
        ))->shouldBeCalled();

        $this->__invoke($this->draftCommand(description: ''));
    }

    public function it_saves_nothing_when_the_draft_is_invalid(
        MovementRepository $movements,
    ): void {
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)
            ->during('__invoke', [$this->draftCommand(title: '   ', description: '')]);
    }

    public function it_rejects_a_draft_whose_payload_omits_the_title(
        MovementRepository $movements,
    ): void {
        $movements->save(Argument::any())->shouldNotBeCalled();

        // An absent field reaches the domain empty rather than falling back to anything.
        $this->shouldThrow(InvalidMovement::class)->during('__invoke', [
            CreateMovementCommand::fromPayload([
                'description' => 'Gardens for all.',
                'category' => 'cooperative',
                'area' => 'international',
            ], UserId::from(self::AUTHOR_ID)),
        ]);
    }

    // `string|null` rather than `?string`: PhpSpec's spec loader rejects `?type` parameters.
    private function draftCommand(
        string $title = 'Community Gardens for Everyone',
        string $description = "## Why\nGardens for all.",
        string $category = 'cooperative',
        string $area = 'municipality',
        string|null $location = 'Sheffield',
    ): CreateMovementCommand {
        return CreateMovementCommand::fromPayload([
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'area' => $area,
            'location' => $location,
        ], UserId::from(self::AUTHOR_ID));
    }
}
