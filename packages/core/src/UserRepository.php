<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function findById(string $id): ?User;

    public function save(User $user): void;
}
