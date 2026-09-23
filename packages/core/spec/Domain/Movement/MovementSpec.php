<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Domain\Movement;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\Movement\Area;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementStatus;
use SocialBulletin\Core\Domain\User\UserId;

final class MovementSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(): void
    {
        $this->beConstructedAsADraft();
    }

    public function it_creates_a_draft_with_all_fields(): void
    {
        $this->id->shouldBeLike(MovementId::from(self::ID));
        $this->authorId->shouldBeLike(UserId::from(self::AUTHOR_ID));
        $this->title()->shouldBe('Community Gardens for Everyone');
        $this->description()->shouldBe("## Why\nGardens for all.");
        $this->category()->shouldBe('cooperative');
        $this->area()->shouldBe(Area::Municipality);
        $this->location()->shouldBe('Sheffield');
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_allows_an_empty_description_while_draft(): void
    {
        $this->beConstructedAsADraft(description: '');

        $this->description()->shouldBe('');
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_rejects_a_blank_title(): void
    {
        $this->beConstructedAsADraft(title: '   ', description: '');

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_rejects_a_title_longer_than_200_characters(): void
    {
        $this->beConstructedAsADraft(title: str_repeat('a', 201), description: '');

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_rejects_a_description_longer_than_20000_characters(): void
    {
        $this->beConstructedAsADraft(description: str_repeat('a', 20001));

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_requires_a_location_for_non_international_areas(): void
    {
        $this->beConstructedAsADraft(description: '', location: null);

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_rejects_a_location_for_international_movements(): void
    {
        $this->beConstructedAsADraft(title: 'Global Climate Strike', description: '', area: 'international');

        $this->shouldThrow(InvalidMovement::class)->duringInstantiation();
    }

    public function it_submits_a_described_draft_as_proposed(): void
    {
        $this->submit();

        $this->status()->shouldBe(MovementStatus::Proposed);
    }

    public function it_rejects_submission_while_the_description_is_empty(): void
    {
        $this->beConstructedAsADraft(description: '');

        $this->shouldThrow(InvalidMovement::class)->during('submit');
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_rejects_submitting_a_movement_that_is_not_a_draft(): void
    {
        $this->submit();

        $this->shouldThrow(MovementNotDraft::class)->during('submit');
        $this->status()->shouldBe(MovementStatus::Proposed);
    }

    public function it_edits_every_field_while_draft(): void
    {
        $this->edit('Save All the Bees', 'New description.', 'animal_rights', 'region', 'Yorkshire');

        $this->title()->shouldBe('Save All the Bees');
        $this->description()->shouldBe('New description.');
        $this->category()->shouldBe('animal_rights');
        $this->area()->shouldBe(Area::Region);
        $this->location()->shouldBe('Yorkshire');
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_clears_the_location_when_edited_to_international(): void
    {
        $this->edit('Global Climate Strike', '', 'cooperative', 'international', null);

        $this->location()->shouldBe(null);
    }

    public function it_applies_creation_rules_when_editing(): void
    {
        $this->shouldThrow(InvalidMovement::class)
            ->during('edit', ['   ', '', 'cooperative', 'municipality', 'Sheffield']);
    }

    public function it_refuses_to_edit_a_movement_that_is_not_a_draft(): void
    {
        $this->submit();

        $this->shouldThrow(MovementNotDraft::class)->during('edit', [
            'Save All the Bees',
            'New description.',
            'cooperative',
            'municipality',
            'Sheffield',
        ]);
    }

    public function it_carries_no_location_when_international(): void
    {
        $this->beConstructedAsADraft(
            title: 'Global Climate Strike',
            description: '',
            area: 'international',
            location: null,
        );

        $this->location()->shouldBe(null);
        $this->area()->shouldBe(Area::International);
    }

    public function it_accepts_a_category_that_is_not_in_the_managed_list(): void
    {
        $this->beConstructedAsADraft(description: '', category: 'not-a-real-category');

        $this->category()->shouldBe('not-a-real-category');
    }

    // `string|null` rather than `?string`: PhpSpec's spec loader rejects `?type` parameters.
    private function beConstructedAsADraft(
        string $title = 'Community Gardens for Everyone',
        string $description = "## Why\nGardens for all.",
        string $category = 'cooperative',
        string $area = 'municipality',
        string|null $location = 'Sheffield',
    ): void {
        $this->beConstructedThrough('draft', [
            MovementId::from(self::ID),
            UserId::from(self::AUTHOR_ID),
            $title,
            $description,
            $category,
            $area,
            $location,
        ]);
    }
}
