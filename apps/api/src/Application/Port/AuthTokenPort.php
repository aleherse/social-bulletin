<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Port;

interface AuthTokenPort
{
    public function issueFor(string $userId): string;
}
