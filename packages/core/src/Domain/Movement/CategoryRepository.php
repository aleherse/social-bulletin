<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

use Doctrine\DBAL\Connection;

class CategoryRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return list<Category> in display order
     */
    public function all(): array
    {
        /** @var list<string> $ids */
        $ids = $this->connection->fetchFirstColumn(
            'SELECT id FROM bulletin.categories ORDER BY sort_order, id',
        );

        return array_map(static fn (string $id): Category => new Category($id), $ids);
    }

    public function exists(string $id): bool
    {
        return false !== $this->connection->fetchOne(
            'SELECT 1 FROM bulletin.categories WHERE id = :id',
            [
                'id' => $id,
            ],
        );
    }
}
