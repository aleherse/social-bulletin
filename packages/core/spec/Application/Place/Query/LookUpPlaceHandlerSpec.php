<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Place\Query;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Application\Place\Model\Place;
use SocialBulletin\Core\Application\Place\Provider\PlaceProvider;
use SocialBulletin\Core\Application\Place\Query\LookUpPlaceQuery;
use SocialBulletin\Core\Domain\Movement\Area;
use SocialBulletin\Core\Domain\Place\GeoPoint;
use SocialBulletin\Core\Domain\Place\PlaceLevel;

final class LookUpPlaceHandlerSpec extends ObjectBehavior
{
    public function let(PlaceProvider $places): void
    {
        $this->beConstructedWith($places);
    }

    public function it_returns_the_place_the_provider_names_at_the_point(PlaceProvider $places): void
    {
        $place = new Place('Huelin');
        $places->nameAt(
            GeoPoint::fromDegrees(36.7033, -4.4366),
            PlaceLevel::forArea(Area::Neighborhood),
        )->willReturn($place);

        $this->__invoke(self::huelin())->shouldReturn($place);
    }

    public function it_returns_null_when_no_place_is_at_that_level(PlaceProvider $places): void
    {
        $places->nameAt(
            GeoPoint::fromDegrees(36.7033, -4.4366),
            PlaceLevel::forArea(Area::Neighborhood),
        )->willReturn(null);

        $this->__invoke(self::huelin())->shouldReturn(null);
    }

    private static function huelin(): LookUpPlaceQuery
    {
        return LookUpPlaceQuery::fromPayload([
            'lat' => 36.7033,
            'lon' => -4.4366,
            'area' => 'neighborhood',
        ]);
    }
}
