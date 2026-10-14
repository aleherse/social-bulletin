<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User\Model;

use SocialBulletin\Core\Domain\User\UserId;

final readonly class User
{
    public function __construct(
        public UserId $id,
        public string $email,
    ) {
    }
}
