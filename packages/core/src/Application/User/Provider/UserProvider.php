<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User\Provider;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\Application\User\Model\User;
use Webmozart\Assert\Assert;

class UserProvider
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function byEmail(string $email): ?User
    {
        Assert::stringNotEmpty($email);

        /** @var array{id: string, email: string}|false $row */
        $row = $this->connection->createQueryBuilder()
            ->select('id', 'email')
            ->from('bulletin.users')
            ->where('LOWER(email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->fetchAssociative();

        return false === $row ? null : new User($row['id'], $row['email']);
    }
}
