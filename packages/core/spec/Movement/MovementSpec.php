<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Movement;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Movement\Area;
use SocialBulletin\Core\Movement\MovementNotDraft;
use SocialBulletin\Core\Movement\MovementStatus;

final class MovementSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            "## Why\nGardens for all.",
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable('2026-07-19T10:00:00+00:00'),
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
        $this->updatedAt()->shouldBeLike(new \DateTimeImmutable('2026-07-19T10:00:00+00:00'));
    }

    public function it_allows_an_empty_description_while_draft(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        ]);

        $this->description()->shouldBe('');
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_rejects_a_blank_title(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            '   ',
            '',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    public function it_rejects_a_title_longer_than_200_characters(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            str_repeat('a', 201),
            '',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    public function it_rejects_a_description_longer_than_20000_characters(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            str_repeat('a', 20001),
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    public function it_requires_a_location_for_non_international_areas(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            Area::Municipality,
            null,
            new \DateTimeImmutable(),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    public function it_rejects_a_location_for_international_movements(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            'Global Climate Strike',
            '',
            'cooperative',
            Area::International,
            'Sheffield',
            new \DateTimeImmutable(),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    public function it_submits_a_described_draft_as_proposed(): void
    {
        $submittedAt = new \DateTimeImmutable('2026-07-19T12:00:00+00:00');

        $this->submit($submittedAt);

        $this->status()->shouldBe(MovementStatus::Proposed);
        $this->updatedAt()->shouldBeLike($submittedAt);
    }

    public function it_rejects_submission_while_the_description_is_empty(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)
            ->during('submit', [new \DateTimeImmutable()]);
        $this->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_rejects_submitting_a_movement_that_is_not_a_draft(): void
    {
        $this->submit(new \DateTimeImmutable());

        $this->shouldThrow(MovementNotDraft::class)
            ->during('submit', [new \DateTimeImmutable()]);
        $this->status()->shouldBe(MovementStatus::Proposed);
    }

    public function it_edits_every_field_while_draft(): void
    {
        $editedAt = new \DateTimeImmutable('2026-07-19T13:00:00+00:00');

        $this->edit(
            'Save All the Bees',
            'New description.',
            'animal_rights',
            Area::Region,
            'Yorkshire',
            $editedAt,
        );

        $this->title()->shouldBe('Save All the Bees');
        $this->description()->shouldBe('New description.');
        $this->category()->shouldBe('animal_rights');
        $this->area()->shouldBe(Area::Region);
        $this->location()->shouldBe('Yorkshire');
        $this->status()->shouldBe(MovementStatus::Draft);
        $this->updatedAt()->shouldBeLike($editedAt);
    }

    public function it_clears_the_location_when_edited_to_international(): void
    {
        $this->edit(
            'Global Climate Strike',
            '',
            'cooperative',
            Area::International,
            null,
            new \DateTimeImmutable(),
        );

        $this->location()->shouldBe(null);
    }

    public function it_applies_creation_rules_when_editing(): void
    {
        $this->shouldThrow(\InvalidArgumentException::class)->during('edit', [
            '   ',
            '',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        ]);
    }

    public function it_refuses_to_edit_a_movement_that_is_not_a_draft(): void
    {
        $this->submit(new \DateTimeImmutable());

        $this->shouldThrow(MovementNotDraft::class)->during('edit', [
            'Save All the Bees',
            'New description.',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        ]);
    }

    public function it_carries_no_location_when_international(): void
    {
        $this->beConstructedThrough('draft', [
            self::ID,
            self::AUTHOR_ID,
            'Global Climate Strike',
            '',
            'cooperative',
            Area::International,
            null,
            new \DateTimeImmutable(),
        ]);

        $this->location()->shouldBe(null);
        $this->area()->shouldBe(Area::International);
    }
}
