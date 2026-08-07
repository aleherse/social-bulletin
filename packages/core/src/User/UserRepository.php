<?php

declare(strict_types=1);

namespace SocialBulletin\Core\User;

use Doctrine\DBAL\Connection;

class UserRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Lookup is case-insensitive on email.
     */
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
