<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use SocialBulletin\Api\Application\Model\User;
use SocialBulletin\Api\Application\Port\UserRepositoryPort;
use SocialBulletin\Api\Application\ValueObject\EmailAddress;

final class DoctrineUserRepository implements UserRepositoryPort
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email, password_hash, roles, terms_accepted_at, registered_at FROM api_users WHERE email = ?',
            [$email->value()]
        );

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(User $user): void
    {
        $this->connection->executeStatement(
            'INSERT INTO api_users (id, email, password_hash, roles, terms_accepted_at, registered_at)
             VALUES (:id, :email, :password_hash, :roles, :terms_accepted_at, :registered_at)',
            [
                'id'               => $user->id(),
                'email'            => $user->email()->value(),
                'password_hash'    => $user->passwordHash(),
                'roles'            => json_encode($user->roles(), JSON_THROW_ON_ERROR),
                'terms_accepted_at' => $user->termsAcceptedAt()->format(\DateTimeInterface::ATOM),
                'registered_at'    => $user->registeredAt()->format(\DateTimeInterface::ATOM),
            ]
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        /** @var list<string> $roles */
        $roles = json_decode((string) $row['roles'], true, 512, JSON_THROW_ON_ERROR);

        return new User(
            id: (string) $row['id'],
            email: new EmailAddress((string) $row['email']),
            passwordHash: (string) $row['password_hash'],
            termsAcceptedAt: new \DateTimeImmutable((string) $row['terms_accepted_at']),
            registeredAt: new \DateTimeImmutable((string) $row['registered_at']),
            roles: $roles,
        );
    }
}
