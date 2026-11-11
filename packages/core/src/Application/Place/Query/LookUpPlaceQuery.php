<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Place\Query;

use SocialBulletin\Core\Application\Helper\BaseQuery;
use SocialBulletin\Core\Application\Place\Model\Place;
use SocialBulletin\Core\Domain\Movement\Area;
use SocialBulletin\Core\Domain\Place\GeoPoint;
use SocialBulletin\Core\Domain\Place\InvalidPlaceLookup;
use SocialBulletin\Core\Domain\Place\PlaceLevel;

/**
 * @extends BaseQuery<Place>
 */
final readonly class LookUpPlaceQuery extends BaseQuery
{
    public GeoPoint $point;

    public PlaceLevel $level;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws InvalidPlaceLookup naming the field, never echoing its value (FR-005)
     */
    private function __construct(array $payload)
    {
        $this->point = GeoPoint::fromDegrees(
            self::degrees($payload, 'lat'),
            self::degrees($payload, 'lon'),
        );

        $area = $payload['area'] ?? null;
        $this->level = PlaceLevel::forArea(
            (\is_string($area) ? Area::tryFrom($area) : null) ?? throw self::invalid('area', 'place.area.unsupported'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function degrees(array $payload, string $field): float
    {
        $value = $payload[$field] ?? null;

        if (! \is_int($value) && ! \is_float($value)) {
            throw self::invalid($field, 'place.point.out_of_range');
        }

        return (float) $value;
    }

    private static function invalid(string $field, string $key): InvalidPlaceLookup
    {
        return new InvalidPlaceLookup([
            $field => $key,
        ], 'place.lookup.invalid');
    }
}
