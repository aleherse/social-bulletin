<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\UseCase;

use SocialBulletin\Api\Application\Command\RegisterUserCommand;
use SocialBulletin\Api\Application\Event\UserRegistered;
use SocialBulletin\Api\Application\Exception\DuplicateEmailException;
use SocialBulletin\Api\Application\Exception\TermsNotAcceptedException;
use SocialBulletin\Api\Application\Model\User;
use SocialBulletin\Api\Application\Port\AuthTokenPort;
use SocialBulletin\Api\Application\Port\PasswordHasherPort;
use SocialBulletin\Api\Application\Port\UserRepositoryPort;
use SocialBulletin\Api\Application\Port\UuidGeneratorPort;
use SocialBulletin\Api\Application\ValueObject\EmailAddress;

final class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryPort $repository,
        private readonly PasswordHasherPort $hasher,
        private readonly UuidGeneratorPort $uuidGenerator,
        private readonly AuthTokenPort $tokenIssuer,
    ) {
    }

    public function execute(RegisterUserCommand $command): string
    {
        if (!$command->termsAccepted) {
            throw new TermsNotAcceptedException();
        }

        $email = new EmailAddress($command->email);

        if ($this->repository->findByEmail($email) !== null) {
            throw new DuplicateEmailException($email->value());
        }

        $id           = $this->uuidGenerator->generate();
        $passwordHash = $this->hasher->hash($command->rawPassword);
        $now          = new \DateTimeImmutable();

        $user = new User(
            id: $id,
            email: $email,
            passwordHash: $passwordHash,
            termsAcceptedAt: $now,
            registeredAt: $now,
        );

        $this->repository->save($user);

        $event = new UserRegistered(userId: $id, registeredAt: $now);
        unset($event);

        return $this->tokenIssuer->issueFor($id);
    }
}
