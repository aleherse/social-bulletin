<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use Symfony\Component\Uid\Uuid;

class MovementProvider
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return list<Movement> newest first
     */
    public function byAuthor(string $authorId): array
    {
        /** @var list<array<string, string|null>> $rows */
        $rows = $this->getQueryBuilder()
            ->where('author_id = :author_id')
            ->orderBy('created_at', 'DESC')
            ->addOrderBy('id', 'DESC')
            ->setParameter('author_id', $authorId)
            ->fetchAllAssociative();

        return array_map($this->hydrate(...), $rows);
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     */
    public function authorMovement(string $id, string $authorId): Movement
    {
        // Route parameters are arbitrary text; PostgreSQL rejects
        // non-UUID values on a UUID column instead of returning no rows.
        if (! Uuid::isValid($id)) {
            throw new MovementNotFound('movement.not_found');
        }

        /** @var array<string, string|null>|false $row */
        $row = $this->getQueryBuilder()
            ->where('id = :id')
            ->andWhere('author_id = :author_id')
            ->setParameter('id', $id)
            ->setParameter('author_id', $authorId)
            ->fetchAssociative();

        if (false === $row) {
            throw new MovementNotFound('movement.not_found');
        }

        return $this->hydrate($row);
    }

    /**
     * Every read starts here, so the selected columns are declared once.
     */
    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select(
                'id',
                'title',
                'description',
                'category',
                'area',
                'location',
                'status',
                'created_at',
                'updated_at',
            )
            ->from('bulletin.movements');
    }

    /**
     * @param array<string, string|null> $row
     */
    private function hydrate(array $row): Movement
    {
        return new Movement(
            (string) $row['id'],
            (string) $row['title'],
            (string) $row['description'],
            (string) $row['category'],
            (string) $row['area'],
            $row['location'],
            (string) $row['status'],
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
