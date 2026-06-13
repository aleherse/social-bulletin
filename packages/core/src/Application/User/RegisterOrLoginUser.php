<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User;

use SocialBulletin\Core\Domain\User\User;
use SocialBulletin\Core\Domain\User\UserRepository;

final class RegisterOrLoginUser
{
    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

    public function execute(string $email): User
    {
        $existing = $this->users->findByEmail($email);

        if ($existing !== null) {
            return $existing;
        }

        $user = User::register($email);
        $this->users->save($user);

        return $user;
    }
}
