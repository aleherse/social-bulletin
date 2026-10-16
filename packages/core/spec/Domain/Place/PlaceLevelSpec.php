<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Domain\Place;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\Movement\Area;
use SocialBulletin\Core\Domain\Place\InvalidPlaceLookup;

final class PlaceLevelSpec extends ObjectBehavior
{
    public function it_maps_national_to_admin_level_2(): void
    {
        $this->beConstructedThrough('forArea', [Area::National]);

        $this->adminLevel()->shouldBe(2);
        $this->usesNeighbourhoodFallback()->shouldBe(false);
    }

    public function it_maps_state_to_admin_level_4(): void
    {
        $this->beConstructedThrough('forArea', [Area::State]);

        $this->adminLevel()->shouldBe(4);
        $this->usesNeighbourhoodFallback()->shouldBe(false);
    }

    public function it_maps_province_to_admin_level_6(): void
    {
        $this->beConstructedThrough('forArea', [Area::Province]);

        $this->adminLevel()->shouldBe(6);
        $this->usesNeighbourhoodFallback()->shouldBe(false);
    }

    public function it_maps_region_to_admin_level_7(): void
    {
        $this->beConstructedThrough('forArea', [Area::Region]);

        $this->adminLevel()->shouldBe(7);
        $this->usesNeighbourhoodFallback()->shouldBe(false);
    }

    public function it_maps_municipality_to_admin_level_8(): void
    {
        $this->beConstructedThrough('forArea', [Area::Municipality]);

        $this->adminLevel()->shouldBe(8);
        $this->usesNeighbourhoodFallback()->shouldBe(false);
    }

    public function it_maps_neighborhood_to_admin_level_10_with_the_neighbourhood_fallback(): void
    {
        $this->beConstructedThrough('forArea', [Area::Neighborhood]);

        $this->adminLevel()->shouldBe(10);
        $this->usesNeighbourhoodFallback()->shouldBe(true);
    }

    public function it_has_no_level_for_international(): void
    {
        $this->beConstructedThrough('forArea', [Area::International]);

        $this->shouldThrow(new InvalidPlaceLookup([
            'area' => 'place.area.unsupported',
        ], 'place.lookup.invalid'))->duringInstantiation();
    }
}
