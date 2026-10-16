<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Place;

final readonly class GeoPoint
{
    private function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }

    /**
     * @throws InvalidPlaceLookup naming the field out of range, never its value (FR-005)
     */
    public static function fromDegrees(float $latitude, float $longitude): self
    {
        if (! ($latitude >= -90.0 && $latitude <= 90.0)) {
            throw self::outOfRange('lat');
        }

        if (! ($longitude >= -180.0 && $longitude <= 180.0)) {
            throw self::outOfRange('lon');
        }

        return new self($latitude, $longitude);
    }

    private static function outOfRange(string $field): InvalidPlaceLookup
    {
        return new InvalidPlaceLookup([
            $field => 'place.point.out_of_range',
        ], 'place.lookup.invalid');
    }
}
