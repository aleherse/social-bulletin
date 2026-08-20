<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Query\QueryBuilder;
use SocialBulletin\Core\Domain\User\UserId;
use Symfony\Component\Uid\Uuid;

class MovementRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Inserts the movement or updates it when the id already exists, then reads the stored
     * row back as a fresh aggregate — including the timestamps the database assigned.
     *
     * @throws InvalidMovement when the category isn't in the managed list
     */
    public function save(Movement $movement): Movement
    {
        try {
            $this->connection->executeStatement(<<<'SQL'
                INSERT INTO bulletin.movements
                    (id, author_id, title, description, category, area, location, status, created_at, updated_at)
                VALUES
                    (:id, :author_id, :title, :description, :category, :area, :location, :status, now(), now())
                ON CONFLICT (id) DO UPDATE SET
                    title = EXCLUDED.title,
                    description = EXCLUDED.description,
                    category = EXCLUDED.category,
                    area = EXCLUDED.area,
                    location = EXCLUDED.location,
                    status = EXCLUDED.status,
                    updated_at = now()
                SQL
                , [
                    'id' => (string) $movement->id,
                                'author_id' => (string) $movement->authorId,
                                'title' => $movement->title(),
                                'description' => $movement->description(),
                                'category' => $movement->category(),
                                'area' => $movement->area()
                                    ->value,
                                'location' => $movement->location(),
                                'status' => $movement->status()
                                    ->value,
                ]);
        } catch (ForeignKeyConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'movements_category_fk')) {
                throw $exception;
            }

            throw new InvalidMovement([
                'category' => 'movement.category.unknown',
            ], 'movement.invalid', $exception);
        }

        $saved = $this->byId((string) $movement->id);

        if (null === $saved) {
            throw new \RuntimeException('The saved movement could not be read back.');
        }

        return $saved;
    }

    public function byId(string $id): ?Movement
    {
        // Route parameters are arbitrary text; PostgreSQL rejects
        // non-UUID values on a UUID column instead of returning no rows.
        if (! Uuid::isValid($id)) {
            return null;
        }

        /** @var array<string, string|null>|false $row */
        $row = $this->getQueryBuilder()
            ->where('id = :id')
            ->setParameter('id', $id)
            ->fetchAssociative();

        return false === $row ? null : $this->hydrate($row);
    }

    /**
     * @return list<Movement> newest first
     */
    public function byAuthor(UserId $authorId): array
    {
        /** @var list<array<string, string|null>> $rows */
        $rows = $this->getQueryBuilder()
            ->where('author_id = :author_id')
            ->orderBy('created_at', 'DESC')
            ->addOrderBy('id', 'DESC')
            ->setParameter('author_id', (string) $authorId)
            ->fetchAllAssociative();

        return array_map($this->hydrate(...), $rows);
    }

    /**
     * Every read starts here, so the selected columns are declared once.
     */
    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select(
                'id',
                'author_id',
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
        return Movement::restore(
            MovementId::from((string) $row['id']),
            UserId::from((string) $row['author_id']),
            (string) $row['title'],
            (string) $row['description'],
            (string) $row['category'],
            Area::from((string) $row['area']),
            $row['location'],
            MovementStatus::from((string) $row['status']),
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
