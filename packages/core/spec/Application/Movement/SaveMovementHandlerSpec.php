<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\Movement\SaveMovementCommand;
use SocialBulletin\Core\Domain\Helper\IdentityGenerator;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementService;
use SocialBulletin\Core\Domain\Movement\MovementStatus;

final class SaveMovementHandlerSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    // `MovementService` is final, so the real service runs on top of the doubled repository.
    public function let(
        MovementRepository $movements,
        IdentityGenerator $identities,
    ): void {
        $this->beConstructedWith(new MovementService($movements->getWrappedObject()), $movements, $identities);
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
            SaveMovementCommand::fromPayload([
                'description' => 'Gardens for all.',
                'category' => 'cooperative',
                'area' => 'international',
            ], self::AUTHOR_ID),
        ]);
    }

    public function it_saves_the_edited_draft(
        MovementRepository $movements,
    ): void {
        $movement = self::describedDraft();
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save($movement)->willReturn($movement)
            ->shouldBeCalled();

        $updated = $this->__invoke($this->editCommand());

        $updated->title()->shouldBe('Save All the Bees');
        $updated->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_leaves_the_fields_an_edit_payload_omits_untouched(
        MovementRepository $movements,
    ): void {
        $movement = self::describedDraft();
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save($movement)->willReturn($movement)
            ->shouldBeCalled();

        $updated = $this->__invoke(SaveMovementCommand::fromPayload([
            'title' => 'Save All the Bees',
        ], self::AUTHOR_ID, self::ID));

        $updated->title()->shouldBe('Save All the Bees');
        $updated->category()->shouldBe('cooperative');
        $updated->location()->shouldBe('Sheffield');
    }

    public function it_clears_a_location_the_edit_payload_sets_to_null(
        MovementRepository $movements,
    ): void {
        $movement = self::describedDraft();
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save($movement)->willReturn($movement)
            ->shouldBeCalled();

        // An explicit `null` is a value, not an omission: `hasProperty()` keeps them apart.
        $updated = $this->__invoke(SaveMovementCommand::fromPayload([
            'area' => 'international',
            'location' => null,
        ], self::AUTHOR_ID, self::ID));

        $updated->location()->shouldBeNull();
    }

    public function it_refuses_to_edit_a_movement_that_left_draft(
        MovementRepository $movements,
    ): void {
        $movement = self::describedDraft();
        $movement->submit();
        $movements->byId(self::ID)->willReturn($movement);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotDraft::class)->during('__invoke', [$this->editCommand()]);
    }

    public function it_saves_nothing_when_the_edit_is_invalid(
        MovementRepository $movements,
    ): void {
        $movements->byId(self::ID)->willReturn(self::describedDraft());
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(InvalidMovement::class)
            ->during('__invoke', [$this->editCommand(title: '   ')]);
    }

    public function it_hides_a_movement_that_belongs_to_another_author(
        MovementRepository $movements,
    ): void {
        $movements->byId(self::ID)->willReturn(self::describedDraft());
        $movements->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(MovementNotFound::class)->during('__invoke', [
            $this->editCommand(authorId: '0198f2f0-6d2c-7cf0-a2b8-333333333333'),
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

    private static function describedDraft(): Movement
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

    // `string|null` rather than `?string`: PhpSpec's spec loader rejects `?type` parameters.
    private function draftCommand(
        string $title = 'Community Gardens for Everyone',
        string $description = "## Why\nGardens for all.",
        string $category = 'cooperative',
        string $area = 'municipality',
        string|null $location = 'Sheffield',
    ): SaveMovementCommand {
        return SaveMovementCommand::fromPayload([
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'area' => $area,
            'location' => $location,
        ], self::AUTHOR_ID);
    }

    private function editCommand(
        string $authorId = self::AUTHOR_ID,
        string $title = 'Save All the Bees',
        string $description = 'New description.',
        string $category = 'animal_rights',
        string $area = 'region',
        string|null $location = 'Yorkshire',
    ): SaveMovementCommand {
        return SaveMovementCommand::fromPayload([
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'area' => $area,
            'location' => $location,
        ], $authorId, self::ID);
    }
}
