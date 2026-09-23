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
     * @throws InvalidMovement when the category isn't in the managed list
     */
    public function save(Movement $movement): void
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
    }

    /**
     * The movement a write is about to mutate. Reading one to render it goes through
     * {@see \SocialBulletin\Core\Application\Movement\Provider\MovementProvider}.
     *
     * @throws MovementNotFound when unknown or owned by another user
     */
    public function authorMovement(string $id, UserId $authorId): Movement
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
            ->setParameter('author_id', (string) $authorId)
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
