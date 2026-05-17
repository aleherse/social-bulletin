<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Unit\Application\UseCase;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SocialBulletin\Api\Application\Command\RegisterUserCommand;
use SocialBulletin\Api\Application\Exception\DuplicateEmailException;
use SocialBulletin\Api\Application\Exception\TermsNotAcceptedException;
use SocialBulletin\Api\Application\Model\User;
use SocialBulletin\Api\Application\Port\AuthTokenPort;
use SocialBulletin\Api\Application\Port\PasswordHasherPort;
use SocialBulletin\Api\Application\Port\UserRepositoryPort;
use SocialBulletin\Api\Application\Port\UuidGeneratorPort;
use SocialBulletin\Api\Application\UseCase\RegisterUserUseCase;
use SocialBulletin\Api\Application\ValueObject\EmailAddress;

final class RegisterUserUseCaseTest extends TestCase
{
    private UserRepositoryPort&MockObject $repository;
    private PasswordHasherPort&MockObject $hasher;
    private UuidGeneratorPort&MockObject $uuidGenerator;
    private AuthTokenPort&MockObject $tokenIssuer;
    private RegisterUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository    = $this->createMock(UserRepositoryPort::class);
        $this->hasher        = $this->createMock(PasswordHasherPort::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorPort::class);
        $this->tokenIssuer   = $this->createMock(AuthTokenPort::class);

        $this->useCase = new RegisterUserUseCase(
            repository: $this->repository,
            hasher: $this->hasher,
            uuidGenerator: $this->uuidGenerator,
            tokenIssuer: $this->tokenIssuer,
        );
    }

    public function test_happy_path_returns_signed_jwt(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->uuidGenerator->method('generate')->willReturn('uuid-v6-string');
        $this->hasher->method('hash')->willReturn('$2y$hashed');
        $this->repository->expects($this->once())->method('save');
        $this->tokenIssuer->method('issueFor')->with('uuid-v6-string')->willReturn('signed.jwt.token');

        $command = new RegisterUserCommand(
            email: 'user@example.com',
            rawPassword: 'Str0ng!Pass',
            termsAccepted: true,
        );

        $token = $this->useCase->execute($command);

        self::assertSame('signed.jwt.token', $token);
    }

    public function test_duplicate_email_throws_duplicate_email_exception(): void
    {
        $existingUser = new User(
            id: 'existing-uuid',
            email: new EmailAddress('user@example.com'),
            passwordHash: '$2y$existing',
            termsAcceptedAt: new \DateTimeImmutable(),
            registeredAt: new \DateTimeImmutable(),
        );

        $this->repository->method('findByEmail')->willReturn($existingUser);

        $this->expectException(DuplicateEmailException::class);

        $this->useCase->execute(new RegisterUserCommand(
            email: 'user@example.com',
            rawPassword: 'Str0ng!Pass',
            termsAccepted: true,
        ));
    }

    public function test_terms_refused_throws_terms_not_accepted_exception(): void
    {
        $this->expectException(TermsNotAcceptedException::class);

        $this->useCase->execute(new RegisterUserCommand(
            email: 'user@example.com',
            rawPassword: 'Str0ng!Pass',
            termsAccepted: false,
        ));
    }

    public function test_user_is_saved_with_hashed_password(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->uuidGenerator->method('generate')->willReturn('uuid-v6-string');
        $this->hasher->method('hash')->with('Str0ng!Pass')->willReturn('$2y$hashed');
        $this->tokenIssuer->method('issueFor')->willReturn('token');

        $savedUser = null;
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $user) use (&$savedUser): void {
                $savedUser = $user;
            });

        $this->useCase->execute(new RegisterUserCommand(
            email: 'user@example.com',
            rawPassword: 'Str0ng!Pass',
            termsAccepted: true,
        ));

        self::assertNotNull($savedUser);
        self::assertSame('uuid-v6-string', $savedUser->id());
        self::assertSame('user@example.com', $savedUser->email()->value());
        self::assertSame('$2y$hashed', $savedUser->passwordHash());
        self::assertSame(['ROLE_USER'], $savedUser->roles());
    }
}
