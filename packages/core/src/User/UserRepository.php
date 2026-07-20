<?php

declare(strict_types=1);

namespace SocialBulletin\Core\User;

interface UserRepository
{
    /**
     * Lookup is case-insensitive on email.
     */
    public function findByEmail(string $email): ?User;

    public function add(User $user): void;
}
