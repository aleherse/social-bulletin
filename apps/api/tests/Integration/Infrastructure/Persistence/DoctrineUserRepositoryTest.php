<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Integration\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use PHPUnit\Framework\TestCase;
use SocialBulletin\Api\Application\Model\User;
use SocialBulletin\Api\Application\ValueObject\EmailAddress;
use SocialBulletin\Api\Infrastructure\Persistence\DoctrineUserRepository;

final class DoctrineUserRepositoryTest extends TestCase
{
    private Connection $connection;
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        $databaseUrl = getenv('DATABASE_URL');
        self::assertNotEmpty($databaseUrl, 'DATABASE_URL must be set for integration tests');

        $params = (new DsnParser(['postgresql' => 'pdo_pgsql', 'postgres' => 'pdo_pgsql']))->parse((string) $databaseUrl);
        $this->connection = DriverManager::getConnection($params);

        $this->connection->executeStatement('DELETE FROM api_users');

        $this->repository = new DoctrineUserRepository($this->connection);
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('DELETE FROM api_users');
        $this->connection->close();
    }

    public function test_find_by_email_returns_null_for_unknown_email(): void
    {
        $result = $this->repository->findByEmail(new EmailAddress('nobody@example.com'));

        self::assertNull($result);
    }

    public function test_save_persists_a_user(): void
    {
        $user = $this->makeUser('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'saved@example.com');

        $this->repository->save($user);

        $row = $this->connection->fetchAssociative(
            'SELECT * FROM api_users WHERE id = ?',
            ['a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11']
        );

        self::assertIsArray($row);
        self::assertSame('saved@example.com', $row['email']);
    }

    public function test_find_by_email_returns_user_after_save(): void
    {
        $user = $this->makeUser('b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a22', 'find@example.com');
        $this->repository->save($user);

        $found = $this->repository->findByEmail(new EmailAddress('find@example.com'));

        self::assertNotNull($found);
        self::assertSame('b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a22', $found->id());
        self::assertSame('find@example.com', $found->email()->value());
    }

    public function test_duplicate_email_raises_unique_constraint_violation(): void
    {
        $user  = $this->makeUser('c2eebc99-9c0b-4ef8-bb6d-6bb9bd380a33', 'dup@example.com');
        $user2 = $this->makeUser('d3eebc99-9c0b-4ef8-bb6d-6bb9bd380a44', 'dup@example.com');

        $this->repository->save($user);

        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

        $this->repository->save($user2);
    }

    private function makeUser(string $id, string $email): User
    {
        return new User(
            id: $id,
            email: new EmailAddress($email),
            passwordHash: '$2y$13$fakehash',
            termsAcceptedAt: new \DateTimeImmutable('2026-01-01T10:00:00Z'),
            registeredAt: new \DateTimeImmutable('2026-01-01T10:00:00Z'),
        );
    }
}
