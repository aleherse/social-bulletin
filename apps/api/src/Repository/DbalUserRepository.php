<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\User\User;
use SocialBulletin\Core\User\UserRepository;

final class DbalUserRepository implements UserRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        /** @var array{id: string, email: string, created_at: string}|false $row */
        $row = $this->connection->fetchAssociative(
            'SELECT id, email, created_at FROM bulletin.users WHERE LOWER(email) = LOWER(:email)',
            [
                'email' => $email,
            ],
        );

        if (false === $row) {
            return null;
        }

        return new User($row['id'], $row['email'], new \DateTimeImmutable($row['created_at']));
    }

    public function add(User $user): void
    {
        $this->connection->insert('bulletin.users', [
            'id' => $user->id,
            'email' => $user->email,
            'created_at' => $user->createdAt->format(\DateTimeInterface::ATOM),
        ]);
    }
}
