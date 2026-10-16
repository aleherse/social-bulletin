<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Place;

final readonly class Neighbourhood
{
    public function __construct(
        public int $osmId,
        public string $name,
        public string $geometryJson,
    ) {
    }
}
