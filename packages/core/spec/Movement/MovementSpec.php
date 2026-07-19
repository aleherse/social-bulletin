<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Movement;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Movement\Area;
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
