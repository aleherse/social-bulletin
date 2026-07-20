<?php

declare(strict_types=1);

namespace SocialBulletin\Core\User;

final readonly class User
{
    public function __construct(
        public string $id,
        public string $email,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
