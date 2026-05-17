<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Infrastructure\Uid;

use SocialBulletin\Api\Application\Port\UuidGeneratorPort;
use Symfony\Component\Uid\UuidV6;

final class SymfonyUidGenerator implements UuidGeneratorPort
{
    public function generate(): string
    {
        return (string) new UuidV6();
    }
}
