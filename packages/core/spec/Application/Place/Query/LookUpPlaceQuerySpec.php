<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Place\Query;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\Movement\Area;
use SocialBulletin\Core\Domain\Place\GeoPoint;
use SocialBulletin\Core\Domain\Place\InvalidPlaceLookup;
use SocialBulletin\Core\Domain\Place\PlaceLevel;

final class LookUpPlaceQuerySpec extends ObjectBehavior
{
    public function it_reads_the_point_and_level_from_the_payload(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lat' => 36.7033,
            'lon' => -4.4366,
            'area' => 'neighborhood',
        ]]);

        $this->point->shouldBeLike(GeoPoint::fromDegrees(36.7033, -4.4366));
        $this->level->shouldBeLike(PlaceLevel::forArea(Area::Neighborhood));
    }

    public function it_accepts_integer_degrees(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lat' => 36,
            'lon' => -4,
            'area' => 'municipality',
        ]]);

        $this->point->shouldBeLike(GeoPoint::fromDegrees(36.0, -4.0));
    }

    public function it_rejects_a_missing_latitude(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lon' => -4.4366,
            'area' => 'neighborhood',
        ]]);

        $this->shouldThrow(self::invalid('lat', 'place.point.out_of_range'))->duringInstantiation();
    }

    public function it_rejects_a_latitude_that_is_not_a_number(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lat' => '36.7033',
            'lon' => -4.4366,
            'area' => 'neighborhood',
        ]]);

        $this->shouldThrow(self::invalid('lat', 'place.point.out_of_range'))->duringInstantiation();
    }

    public function it_rejects_a_missing_longitude(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lat' => 36.7033,
            'area' => 'neighborhood',
        ]]);

        $this->shouldThrow(self::invalid('lon', 'place.point.out_of_range'))->duringInstantiation();
    }

    public function it_rejects_a_longitude_that_is_not_a_number(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lat' => 36.7033,
            'lon' => null,
            'area' => 'neighborhood',
        ]]);

        $this->shouldThrow(self::invalid('lon', 'place.point.out_of_range'))->duringInstantiation();
    }

    public function it_rejects_a_missing_area(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lat' => 36.7033,
            'lon' => -4.4366,
        ]]);

        $this->shouldThrow(self::invalid('area', 'place.area.unsupported'))->duringInstantiation();
    }

    public function it_rejects_an_unknown_area(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lat' => 36.7033,
            'lon' => -4.4366,
            'area' => 'galaxy',
        ]]);

        $this->shouldThrow(self::invalid('area', 'place.area.unsupported'))->duringInstantiation();
    }

    public function it_rejects_international(): void
    {
        $this->beConstructedThrough('fromPayload', [[
            'lat' => 36.7033,
            'lon' => -4.4366,
            'area' => 'international',
        ]]);

        $this->shouldThrow(self::invalid('area', 'place.area.unsupported'))->duringInstantiation();
    }

    private static function invalid(string $field, string $key): InvalidPlaceLookup
    {
        return new InvalidPlaceLookup([
            $field => $key,
        ], 'place.lookup.invalid');
    }
}
