<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Unit\Application\Model;

use PHPUnit\Framework\TestCase;
use SocialBulletin\Api\Application\Model\User;
use SocialBulletin\Api\Application\ValueObject\EmailAddress;

final class UserTest extends TestCase
{
    private function makeUser(array $overrides = []): User
    {
        $defaults = [
            'id'              => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'email'           => new EmailAddress('user@example.com'),
            'passwordHash'    => '$2y$13$hash',
            'termsAcceptedAt' => new \DateTimeImmutable('2026-01-01T10:00:00Z'),
            'registeredAt'    => new \DateTimeImmutable('2026-01-01T10:00:00Z'),
        ];

        $opts = array_merge($defaults, $overrides);

        $args = [
            'id'              => $opts['id'],
            'email'           => $opts['email'],
            'passwordHash'    => $opts['passwordHash'],
            'termsAcceptedAt' => $opts['termsAcceptedAt'],
            'registeredAt'    => $opts['registeredAt'],
        ];

        if (array_key_exists('roles', $opts)) {
            $args['roles'] = $opts['roles'];
        }

        return new User(...$args);
    }

    public function test_fields_are_set_at_construction(): void
    {
        $email           = new EmailAddress('user@example.com');
        $termsAcceptedAt = new \DateTimeImmutable('2026-01-01T10:00:00Z');
        $registeredAt    = new \DateTimeImmutable('2026-01-01T10:00:01Z');

        $user = new User(
            id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            email: $email,
            passwordHash: '$2y$13$hash',
            termsAcceptedAt: $termsAcceptedAt,
            registeredAt: $registeredAt,
        );

        self::assertSame('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', $user->id());
        self::assertSame($email, $user->email());
        self::assertSame('$2y$13$hash', $user->passwordHash());
        self::assertSame($termsAcceptedAt, $user->termsAcceptedAt());
        self::assertSame($registeredAt, $user->registeredAt());
    }

    public function test_roles_default_to_role_user(): void
    {
        $user = $this->makeUser();

        self::assertSame(['ROLE_USER'], $user->roles());
    }

    public function test_custom_roles_are_accepted(): void
    {
        $user = $this->makeUser(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]);

        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->roles());
    }

    public function test_empty_roles_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeUser(['roles' => []]);
    }
}
