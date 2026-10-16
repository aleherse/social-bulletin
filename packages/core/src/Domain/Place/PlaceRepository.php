<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Place;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class PlaceRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Lookups keep reading the previous places until the whole import commits;
     * anything thrown while `$places` is consumed rolls it back.
     *
     * @param iterable<Boundary|Neighbourhood> $places
     */
    public function replaceAll(iterable $places): void
    {
        $this->connection->transactional(static function (Connection $connection) use ($places): void {
            $connection->executeStatement('DELETE FROM bulletin.place_boundaries');
            $connection->executeStatement('DELETE FROM bulletin.place_neighbourhoods');

            $insertBoundary = $connection->prepare(<<<'SQL'
                INSERT INTO bulletin.place_boundaries (osm_id, admin_level, name, geom)
                VALUES (:osm_id, :admin_level, :name, ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326)))
                SQL);
            $insertNeighbourhood = $connection->prepare(<<<'SQL'
                INSERT INTO bulletin.place_neighbourhoods (osm_id, name, geom)
                VALUES (:osm_id, :name, ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326))
                SQL);

            foreach ($places as $place) {
                if ($place instanceof Boundary) {
                    $insertBoundary->bindValue('osm_id', $place->osmId, ParameterType::INTEGER);
                    $insertBoundary->bindValue('admin_level', $place->adminLevel, ParameterType::INTEGER);
                    $insertBoundary->bindValue('name', $place->name);
                    $insertBoundary->bindValue('geometry', $place->geometryJson);
                    $insertBoundary->executeStatement();

                    continue;
                }

                $insertNeighbourhood->bindValue('osm_id', $place->osmId, ParameterType::INTEGER);
                $insertNeighbourhood->bindValue('name', $place->name);
                $insertNeighbourhood->bindValue('geometry', $place->geometryJson);
                $insertNeighbourhood->executeStatement();
            }
        });
    }
}
