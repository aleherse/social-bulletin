<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Model;

/**
 * A category as a reader sees it; carries no behaviour.
 * Produced by {@see \SocialBulletin\Core\Application\Movement\Provider\CategoryProvider}.
 */
final readonly class Category
{
    public function __construct(
        public string $id,
    ) {
    }
}
