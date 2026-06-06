<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Persistence;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\User\EmailAddress;
use SocialBulletin\Core\User\User;
use Symfony\Component\Uid\Uuid;

final readonly class DbalUserRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function findByEmail(EmailAddress $emailAddress): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email FROM bulletin.users WHERE LOWER(email) = LOWER(:email)',
            ['email' => $emailAddress->value()],
        );

        if ($row === false) {
            return null;
        }

        return new User((string) $row['id'], new EmailAddress((string) $row['email']));
    }

    public function findById(string $id): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email FROM bulletin.users WHERE id = :id',
            ['id' => $id],
        );

        if ($row === false) {
            return null;
        }

        return new User((string) $row['id'], new EmailAddress((string) $row['email']));
    }

    public function create(EmailAddress $emailAddress): User
    {
        $user = new User(Uuid::v7()->toRfc4122(), $emailAddress);

        $this->connection->insert('bulletin.users', [
            'id' => $user->id(),
            'email' => $user->emailAddress()->value(),
        ]);

        return $user;
    }
}
