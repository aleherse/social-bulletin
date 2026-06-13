<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\Domain\User\User;
use SocialBulletin\Core\Domain\User\UserRepository;
use Symfony\Component\Uid\Uuid;

final class DbalUserRepository implements UserRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email, created_at FROM bulletin.users WHERE email = :email',
            ['email' => $email],
        );

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findById(string $id): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email, created_at FROM bulletin.users WHERE id = :id',
            ['id' => $id],
        );

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(User $user): void
    {
        $this->connection->insert('bulletin.users', [
            'id' => $user->id()->toRfc4122(),
            'email' => $user->email(),
            'created_at' => $user->createdAt()->format('Y-m-d H:i:sP'),
        ]);
    }

    /**
     * @param array{id: string, email: string, created_at: string} $row
     */
    private function hydrate(array $row): User
    {
        return User::fromPersistence(
            Uuid::fromString($row['id']),
            $row['email'],
            new \DateTimeImmutable($row['created_at']),
        );
    }
}
