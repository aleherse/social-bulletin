<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Domain\Place;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\Place\InvalidPlaceLookup;

final class GeoPointSpec extends ObjectBehavior
{
    public function it_holds_a_point_in_degrees(): void
    {
        $this->beConstructedThrough('fromDegrees', [36.7033, -4.4366]);

        $this->latitude->shouldBe(36.7033);
        $this->longitude->shouldBe(-4.4366);
    }

    public function it_accepts_the_southern_and_western_bounds(): void
    {
        $this->beConstructedThrough('fromDegrees', [-90.0, -180.0]);

        $this->latitude->shouldBe(-90.0);
        $this->longitude->shouldBe(-180.0);
    }

    public function it_accepts_the_northern_and_eastern_bounds(): void
    {
        $this->beConstructedThrough('fromDegrees', [90.0, 180.0]);

        $this->latitude->shouldBe(90.0);
        $this->longitude->shouldBe(180.0);
    }

    // The exact message also proves it carries no coordinate (FR-005).
    public function it_rejects_a_latitude_out_of_range_without_echoing_it(): void
    {
        $this->beConstructedThrough('fromDegrees', [90.0001, 0.0]);

        $this->shouldThrow(new InvalidPlaceLookup([
            'lat' => 'place.point.out_of_range',
        ], 'place.lookup.invalid'))->duringInstantiation();
    }

    public function it_rejects_a_longitude_out_of_range_without_echoing_it(): void
    {
        $this->beConstructedThrough('fromDegrees', [0.0, -180.5]);

        $this->shouldThrow(new InvalidPlaceLookup([
            'lon' => 'place.point.out_of_range',
        ], 'place.lookup.invalid'))->duringInstantiation();
    }
}
