<?php

declare(strict_types=1);

namespace App\Identity;

use SocialBulletin\Core\IdentityGenerator;
use Symfony\Component\Uid\Uuid;

final class UuidV7IdentityGenerator implements IdentityGenerator
{
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
