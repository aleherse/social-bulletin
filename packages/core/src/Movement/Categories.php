<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

interface Categories
{
    /**
     * @return list<Category> in display order
     */
    public function all(): array;

    public function exists(string $id): bool;
}
