<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Port;

use SocialBulletin\Api\Application\Model\User;
use SocialBulletin\Api\Application\ValueObject\EmailAddress;

interface UserRepositoryPort
{
    public function findByEmail(EmailAddress $email): ?User;

    public function save(User $user): void;
}
