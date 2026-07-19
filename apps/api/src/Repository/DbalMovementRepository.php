<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\Movement\Area;
use SocialBulletin\Core\Movement\Movement;
use SocialBulletin\Core\Movement\MovementRepository;
use SocialBulletin\Core\Movement\MovementStatus;
use Symfony\Component\Uid\Uuid;

final class DbalMovementRepository implements MovementRepository
{
    private const COLUMNS = 'id, author_id, title, description, category, area, location, status, created_at, updated_at';

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function save(Movement $movement): void
    {
        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO bulletin.movements
                (id, author_id, title, description, category, area, location, status, created_at, updated_at)
            VALUES
                (:id, :author_id, :title, :description, :category, :area, :location, :status, :created_at, :updated_at)
            ON CONFLICT (id) DO UPDATE SET
                title = EXCLUDED.title,
                description = EXCLUDED.description,
                category = EXCLUDED.category,
                area = EXCLUDED.area,
                location = EXCLUDED.location,
                status = EXCLUDED.status,
                updated_at = EXCLUDED.updated_at
            SQL
            , [
                'id' => $movement->id,
                        'author_id' => $movement->authorId,
                        'title' => $movement->title(),
                        'description' => $movement->description(),
                        'category' => $movement->category(),
                        'area' => $movement->area()
                            ->value,
                        'location' => $movement->location(),
                        'status' => $movement->status()
                            ->value,
                        'created_at' => $movement->createdAt->format(\DateTimeInterface::ATOM),
                        'updated_at' => $movement->updatedAt()
                            ->format(\DateTimeInterface::ATOM),
            ]);
    }

    public function byId(string $id): ?Movement
    {
        // Route parameters are arbitrary text; PostgreSQL rejects
        // non-UUID values on a UUID column instead of returning no rows.
        if (! Uuid::isValid($id)) {
            return null;
        }

        /** @var array<string, string|null>|false $row */
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT %s FROM bulletin.movements WHERE id = :id', self::COLUMNS),
            [
                'id' => $id,
            ],
        );

        return false === $row ? null : $this->hydrate($row);
    }

    public function byAuthor(string $authorId): array
    {
        /** @var list<array<string, string|null>> $rows */
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT %s FROM bulletin.movements WHERE author_id = :author_id ORDER BY created_at DESC, id DESC',
                self::COLUMNS,
            ),
            [
                'author_id' => $authorId,
            ],
        );

        return array_map($this->hydrate(...), $rows);
    }

    /**
     * @param array<string, string|null> $row
     */
    private function hydrate(array $row): Movement
    {
        return Movement::restore(
            (string) $row['id'],
            (string) $row['author_id'],
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
