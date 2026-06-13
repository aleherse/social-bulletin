<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/** @implements UserProviderInterface<User> */
final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $schema,
    ) {}

    public function loadUserByIdentifier(string $identifier): User
    {
        $row = $this->connection->fetchAssociative(
            "SELECT id, email FROM {$this->schema}.users WHERE email = :email",
            ['email' => $identifier],
        );

        if ($row === false) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return new User($row['id'], $row['email']);
    }

    public function refreshUser(UserInterface $user): User
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}
