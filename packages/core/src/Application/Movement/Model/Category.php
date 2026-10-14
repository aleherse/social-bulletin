<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Model;

final readonly class Category
{
    public function __construct(
        public string $id,
    ) {
    }
}
