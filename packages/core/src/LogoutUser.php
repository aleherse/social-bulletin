<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

final readonly class LogoutUser
{
    public function __invoke(): LogoutIntent
    {
        return new LogoutIntent(cookieName: 'token');
    }
}
