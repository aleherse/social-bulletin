<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Repository;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\User;
use SocialBulletin\Core\UserRepository;

final readonly class DbalUserRepository implements UserRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email FROM bulletin.users WHERE email = :email',
            ['email' => strtolower(trim($email))],
        );

        return is_array($row) ? new User((string) $row['id'], (string) $row['email']) : null;
    }

    public function findById(string $id): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email FROM bulletin.users WHERE id = :id',
            ['id' => $id],
        );

        return is_array($row) ? new User((string) $row['id'], (string) $row['email']) : null;
    }

    public function save(User $user): void
    {
        $this->connection->insert('bulletin.users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
