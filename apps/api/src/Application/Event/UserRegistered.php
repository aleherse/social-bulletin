<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Event;

final readonly class UserRegistered
{
    public function __construct(
        public string $userId,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
