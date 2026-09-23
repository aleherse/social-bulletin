<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

final readonly class Category
{
    public function __construct(
        public string $id,
    ) {
    }
}
