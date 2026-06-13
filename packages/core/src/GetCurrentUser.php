<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

final readonly class GetCurrentUser
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    public function __invoke(string $userId): ?User
    {
        return $this->users->findById($userId);
    }
}
