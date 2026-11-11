<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Place\Query;

use SocialBulletin\Core\Application\Place\Model\Place;
use SocialBulletin\Core\Application\Place\Provider\PlaceProvider;

final readonly class LookUpPlaceHandler
{
    public function __construct(
        private PlaceProvider $places,
    ) {
    }

    public function __invoke(LookUpPlaceQuery $query): Place
    {
        return $this->places->nameAt($query->point, $query->level);
    }
}
