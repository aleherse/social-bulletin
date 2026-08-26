<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\User;

use Webmozart\Assert\Assert;

/**
 * Reads users. Signing in — which registers a user the first time — goes through
 * {@see \SocialBulletin\Core\Application\User\SignInCommand} and its handler.
 */
final readonly class UserService
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    public function currentUser(string $email): ?User
    {
        Assert::stringNotEmpty($email);

        return $this->users->findByEmail($email);
    }
}
