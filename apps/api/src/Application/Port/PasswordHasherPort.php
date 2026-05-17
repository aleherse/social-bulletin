<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Port;

interface PasswordHasherPort
{
    public function hash(string $rawPassword): string;
}
