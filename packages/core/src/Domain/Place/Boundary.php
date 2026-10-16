<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Place;

final readonly class Boundary
{
    public function __construct(
        public int $osmId,
        public int $adminLevel,
        public string $name,
        public string $geometryJson,
    ) {
    }
}
