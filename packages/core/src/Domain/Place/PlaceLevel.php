<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Place;

use SocialBulletin\Core\Domain\Movement\Area;

/**
 * The OpenStreetMap `admin_level` a movement's area names.
 */
final readonly class PlaceLevel
{
    private const int NEIGHBOURHOOD = 10;

    private const array AREA_LEVELS = [
        'national' => 2,
        'state' => 4,
        'province' => 6,
        'region' => 7,
        'municipality' => 8,
        'neighborhood' => self::NEIGHBOURHOOD,
    ];

    private function __construct(
        private int $adminLevel,
    ) {
    }

    /**
     * @throws InvalidPlaceLookup for an area no single place stands for
     */
    public static function forArea(Area $area): self
    {
        $adminLevel = self::AREA_LEVELS[$area->value] ?? null;

        if (null === $adminLevel) {
            throw new InvalidPlaceLookup([
                'area' => 'place.area.unsupported',
            ], 'place.lookup.invalid');
        }

        return new self($adminLevel);
    }

    public static function supports(int $adminLevel): bool
    {
        return \in_array($adminLevel, self::AREA_LEVELS, true);
    }

    public function adminLevel(): int
    {
        return $this->adminLevel;
    }

    /**
     * Many cities map neighbourhoods as points rather than level-10 areas.
     */
    public function usesNeighbourhoodFallback(): bool
    {
        return self::NEIGHBOURHOOD === $this->adminLevel;
    }
}
