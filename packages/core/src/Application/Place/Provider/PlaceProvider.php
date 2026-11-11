<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Place\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use SocialBulletin\Core\Application\Place\Model\Place;
use SocialBulletin\Core\Domain\Place\GeoPoint;
use SocialBulletin\Core\Domain\Place\PlaceLevel;

class PlaceProvider
{
    private const string POINT = 'ST_SetSRID(ST_MakePoint(CAST(:lon AS double precision), CAST(:lat AS double precision)), 4326)';

    private const int NEIGHBOURHOOD_RADIUS_METRES = 1000;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function nameAt(GeoPoint $point, PlaceLevel $level): Place
    {
        return $this->boundaryAt($point, $level)
            ?? ($level->usesNeighbourhoodFallback() ? $this->neighbourhoodNear($point) : null)
            ?? new Place(null);
    }

    private function boundaryAt(GeoPoint $point, PlaceLevel $level): ?Place
    {
        $row = $this->getBoundaryQueryBuilder()
            ->where('admin_level = :admin_level')
            ->andWhere(sprintf('ST_Covers(geom, %s)', self::POINT))
            ->orderBy('ST_Area(geom)', 'ASC')
            ->setMaxResults(1)
            ->setParameter('admin_level', $level->adminLevel())
            ->setParameter('lat', $point->latitude)
            ->setParameter('lon', $point->longitude)
            ->fetchAssociative();

        return $this->hydrate($row);
    }

    private function neighbourhoodNear(GeoPoint $point): ?Place
    {
        $row = $this->getNeighbourhoodQueryBuilder()
            ->where(sprintf('ST_DistanceSphere(geom, %s) <= :radius', self::POINT))
            ->orderBy(sprintf('geom <-> %s', self::POINT))
            ->setMaxResults(1)
            ->setParameter('radius', self::NEIGHBOURHOOD_RADIUS_METRES)
            ->setParameter('lat', $point->latitude)
            ->setParameter('lon', $point->longitude)
            ->fetchAssociative();

        return $this->hydrate($row);
    }

    /**
     * The two finders share no table, so each reads from its own builder (database-persistence-0002).
     */
    private function getBoundaryQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('name')
            ->from('bulletin.place_boundaries');
    }

    private function getNeighbourhoodQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('name')
            ->from('bulletin.place_neighbourhoods');
    }

    /**
     * @param array<string, mixed>|false $row
     */
    private function hydrate(array|false $row): ?Place
    {
        return false === $row ? null : new Place((string) $row['name']);
    }
}
