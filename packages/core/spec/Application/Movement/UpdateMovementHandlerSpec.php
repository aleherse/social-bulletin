<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\Movement\UpdateMovementCommand;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementService;
use SocialBulletin\Core\Domain\Movement\MovementStatus;

final class UpdateMovementHandlerSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    // `MovementService` is final, so the real service runs on top of the doubled repository.
    public function let(MovementRepository $movements): void
    {
        $this->beConstructedWith(new MovementService($movements->getWrappedObject()), $movements);
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

        $updated = $this->__invoke(UpdateMovementCommand::fromPayload([
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
        $updated = $this->__invoke(UpdateMovementCommand::fromPayload([
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
    private function editCommand(
        string $authorId = self::AUTHOR_ID,
        string $title = 'Save All the Bees',
        string $description = 'New description.',
        string $category = 'animal_rights',
        string $area = 'region',
        string|null $location = 'Yorkshire',
    ): UpdateMovementCommand {
        return UpdateMovementCommand::fromPayload([
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'area' => $area,
            'location' => $location,
        ], $authorId, self::ID);
    }
}
