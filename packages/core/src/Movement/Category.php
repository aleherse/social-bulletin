<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

final readonly class Category
{
    public function __construct(
        public string $id,
    ) {
    }
}
