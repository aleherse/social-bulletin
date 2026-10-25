<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Model;

final readonly class Movement
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $category,
        public string $area,
        public ?string $location,
        public string $status,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
