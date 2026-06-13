<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

final readonly class LogoutIntent
{
    public function __construct(
        public string $cookieName,
    ) {
    }
}
