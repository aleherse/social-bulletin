<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Movement;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Movement\Area;
use SocialBulletin\Core\Movement\DraftMovement;
use SocialBulletin\Core\Movement\EditMovement;
use SocialBulletin\Core\Movement\InvalidMovement;
use SocialBulletin\Core\Movement\MovementNotDraft;
use SocialBulletin\Core\Movement\MovementStatus;
use SocialBulletin\Core\Movement\SubmitMovement;

final class MovementSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(),
        ]);
    }

    public function it_creates_a_draft_with_all_fields(): void
    {
        $this->id->shouldBe(self::ID);
        $this->authorId->shouldBe(self::AUTHOR_ID);
        $this->title()->shouldBe('Community Gardens for Everyone');
        $this->description()->shouldBe("## Why\nGardens for all.");
        $this->category()->shouldBe('cooperative');
        $this->area()->shouldBe(Area::Municipality);
        $this->location()->shouldBe('Sheffield');
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_allows_an_empty_description_while_draft(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(description: ''),
        ]);

        $this->description()->shouldBe('');
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_rejects_a_blank_title(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(title: '   ', description: ''),
        ]);

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_rejects_a_title_longer_than_200_characters(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(title: str_repeat('a', 201), description: ''),
        ]);

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_rejects_a_description_longer_than_20000_characters(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(description: str_repeat('a', 20001)),
        ]);

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_requires_a_location_for_non_international_areas(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(description: '', location: null),
        ]);

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_rejects_a_location_for_international_movements(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(title: 'Global Climate Strike', description: '', area: 'international'),
        ]);

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_submits_a_described_draft_as_proposed(): void
    {
        $this->apply(new SubmitMovement());

        $this->status()->shouldBe(MovementStatus::Proposed);
    }

    public function it_rejects_submission_while_the_description_is_empty(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(description: ''),
        ]);

        $this->shouldThrow(InvalidMovement::class)
            ->during('apply', [new SubmitMovement()]);
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_rejects_submitting_a_movement_that_is_not_a_draft(): void
    {
        $this->apply(new SubmitMovement());

        $this->shouldThrow(MovementNotDraft::class)
            ->during('apply', [new SubmitMovement()]);
        $this->status()->shouldBe(MovementStatus::Proposed);
    }

    public function it_edits_every_field_while_draft(): void
    {
        $this->apply(new EditMovement(
            'Save All the Bees',
            'New description.',
            'animal_rights',
            'region',
            'Yorkshire',
        ));

        $this->title()->shouldBe('Save All the Bees');
        $this->description()->shouldBe('New description.');
        $this->category()->shouldBe('animal_rights');
        $this->area()->shouldBe(Area::Region);
        $this->location()->shouldBe('Yorkshire');
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_clears_the_location_when_edited_to_international(): void
    {
        $this->apply(new EditMovement('Global Climate Strike', '', 'cooperative', 'international', null));

        $this->location()->shouldBe(null);
    }

    public function it_applies_creation_rules_when_editing(): void
    {
        $this->shouldThrow(InvalidMovement::class)->during('apply', [
            new EditMovement('   ', '', 'cooperative', 'municipality', 'Sheffield'),
        ]);
    }

    public function it_refuses_to_edit_a_movement_that_is_not_a_draft(): void
    {
        $this->apply(new SubmitMovement());

        $this->shouldThrow(MovementNotDraft::class)->during('apply', [
            new EditMovement(
                'Save All the Bees',
                'New description.',
                'cooperative',
                'municipality',
                'Sheffield',
            ),
        ]);
    }

    public function it_carries_no_location_when_international(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(
                title: 'Global Climate Strike',
                description: '',
                area: 'international',
                location: null,
            ),
        ]);

        $this->location()->shouldBe(null);
        $this->area()->shouldBe(Area::International);
    }

    public function it_accepts_a_category_that_is_not_in_the_managed_list(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            $this->draftCommand(description: '', category: 'not-a-real-category'),
        ]);

        $this->category()->shouldBe('not-a-real-category');
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
