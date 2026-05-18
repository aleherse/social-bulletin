<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

interface UserRepository
{
    /**
     * Lookup is case-insensitive on email.
     */
    public function findByEmail(string $email): ?User;

    public function add(User $user): void;
}
