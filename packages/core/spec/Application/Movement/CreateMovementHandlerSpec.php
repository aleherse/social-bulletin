<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\Movement\CreateMovementCommand;
use SocialBulletin\Core\Domain\Helper\IdentityGenerator;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementStatus;

final class CreateMovementHandlerSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $this->beConstructedWith($movements, $identities);
        $identities->generate()->willReturn(self::ID);
    }

    public function it_saves_a_new_draft_under_a_generated_identity(
        MovementRepository $movements,
    ): void {
        $movements->save(Argument::that(
            static fn (Movement $movement): bool => self::ID === $movement->id
                && self::AUTHOR_ID === $movement->authorId
                && MovementStatus::Draft === $movement->status(),
        ))->will(self::echoesBackTheSavedMovement())
            ->shouldBeCalled();

        $movement = $this->__invoke($this->draftCommand());

        $movement->title()->shouldBe('Community Gardens for Everyone');
        $movement->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_saves_a_draft_with_an_empty_description(
        MovementRepository $movements,
    ): void {
        $movements->save(Argument::type(Movement::class))
            ->will(self::echoesBackTheSavedMovement())
            ->shouldBeCalled();

        $this->__invoke($this->draftCommand(description: ''))
            ->description()
            ->shouldBe('');
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
            ], self::AUTHOR_ID),
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
        ], self::AUTHOR_ID);
    }
}
