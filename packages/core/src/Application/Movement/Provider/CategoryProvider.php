<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Provider;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\Application\Movement\Model\Category;

/**
 * Reads the managed category list. Nothing in the application writes it;
 * `bulletin.categories` is seeded by migration.
 */
class CategoryProvider
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
}
