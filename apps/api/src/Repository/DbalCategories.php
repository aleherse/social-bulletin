<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\Movement\Categories;
use SocialBulletin\Core\Movement\Category;

final class DbalCategories implements Categories
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

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
