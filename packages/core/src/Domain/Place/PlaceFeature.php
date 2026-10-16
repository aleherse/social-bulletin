<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Place;

/**
 * One line of a place import: a GeoJSON feature in the shape `make map-data` writes.
 */
final class PlaceFeature
{
    private const array BOUNDARY_GEOMETRIES = ['Polygon', 'MultiPolygon'];

    private const array NEIGHBOURHOOD_GEOMETRIES = ['Point'];

    /**
     * @param array<mixed> $feature
     *
     * @throws InvalidPlaceFeed naming the line
     */
    public static function parse(int $line, array $feature): Boundary|Neighbourhood
    {
        $properties = $feature['properties'] ?? null;
        if (! \is_array($properties)) {
            throw InvalidPlaceFeed::atLine($line, 'no properties');
        }

        $osmId = $properties['osm_id'] ?? null;
        if (! \is_int($osmId)) {
            throw InvalidPlaceFeed::atLine($line, 'no integer osm_id');
        }

        $name = $properties['name'] ?? null;
        if (! \is_string($name) || '' === trim($name)) {
            throw InvalidPlaceFeed::atLine($line, 'no name');
        }

        $geometry = $feature['geometry'] ?? null;
        if (! \is_array($geometry)) {
            throw InvalidPlaceFeed::atLine($line, 'no geometry');
        }

        return match ($properties['kind'] ?? null) {
            'boundary' => new Boundary(
                $osmId,
                self::adminLevel($line, $properties['admin_level'] ?? null),
                $name,
                self::geometryJson($line, $geometry, self::BOUNDARY_GEOMETRIES),
            ),
            'neighbourhood' => new Neighbourhood(
                $osmId,
                $name,
                self::geometryJson($line, $geometry, self::NEIGHBOURHOOD_GEOMETRIES),
            ),
            default => throw InvalidPlaceFeed::atLine($line, 'kind is neither boundary nor neighbourhood'),
        };
    }

    private static function adminLevel(int $line, mixed $adminLevel): int
    {
        if (! \is_int($adminLevel) || ! PlaceLevel::supports($adminLevel)) {
            throw InvalidPlaceFeed::atLine($line, 'admin_level is not one of 2, 4, 6, 7, 8, 10');
        }

        return $adminLevel;
    }

    /**
     * @param array<mixed> $geometry
     * @param list<string> $allowedTypes
     */
    private static function geometryJson(int $line, array $geometry, array $allowedTypes): string
    {
        if (! \in_array($geometry['type'] ?? null, $allowedTypes, true)) {
            throw InvalidPlaceFeed::atLine($line, sprintf('geometry is not a %s', implode(' or ', $allowedTypes)));
        }

        return json_encode($geometry, JSON_THROW_ON_ERROR);
    }
}
