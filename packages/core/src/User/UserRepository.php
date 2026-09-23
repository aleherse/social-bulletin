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

        return false === $row ? null : $this->hydrate($row);
    }

    /**
     * Inserts the user and returns the stored row as a fresh aggregate — including the
     * creation timestamp the database assigned.
     */
    public function save(User $user): User
    {
        /** @var array{id: string, email: string, created_at: string}|false $row */
        $row = $this->connection->fetchAssociative(<<<'SQL'
            INSERT INTO bulletin.users
                (id, email, created_at)
            VALUES
                (:id, :email, now())
            RETURNING id, email, created_at
            SQL
            , [
                        'id' => $user->id,
                        'email' => $user->email,
                    ]);

        if (false === $row) {
            throw new \RuntimeException('Saving a user returned no row.');
        }

        return $this->hydrate($row);
    }

    /**
     * @param array{id: string, email: string, created_at: string} $row
     */
    private function hydrate(array $row): User
    {
        return User::restore($row['id'], $row['email'], new \DateTimeImmutable($row['created_at']));
    }
}
