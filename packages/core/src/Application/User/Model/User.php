<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User\Model;

use SocialBulletin\Core\Domain\User\UserId;

/**
 * A user as a reader sees it; carries no behaviour.
 * Produced by {@see \SocialBulletin\Core\Application\User\Provider\UserProvider}.
 */
final readonly class User
{
    public function __construct(
        public UserId $id,
        public string $email,
    ) {
    }
}
