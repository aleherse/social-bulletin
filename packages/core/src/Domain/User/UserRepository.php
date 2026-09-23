<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\User;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class UserRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        /** @var array{id: string, email: string, created_at: string}|false $row */
        $row = $this->getQueryBuilder()
            ->where('LOWER(email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->fetchAssociative();

        return false === $row ? null : $this->hydrate($row);
    }

    public function save(User $user): void
    {
        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO bulletin.users
                (id, email, created_at)
            VALUES
                (:id, :email, now())
            SQL
            , [
                'id' => (string) $user->id,
                        'email' => $user->email,
            ]);
    }

    /**
     * Every read starts here, so the selected columns are declared once.
     */
    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('id', 'email', 'created_at')
            ->from('bulletin.users');
    }

    /**
     * @param array{id: string, email: string, created_at: string} $row
     */
    private function hydrate(array $row): User
    {
        return User::restore(UserId::from($row['id']), $row['email'], new \DateTimeImmutable($row['created_at']));
    }
}
