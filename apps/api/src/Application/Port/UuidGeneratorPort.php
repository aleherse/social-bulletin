<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Port;

interface UuidGeneratorPort
{
    public function generate(): string;
}
