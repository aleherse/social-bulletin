<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Infrastructure\Security;

use SocialBulletin\Api\Application\Port\PasswordHasherPort;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class SymfonyPasswordHasherAdapter implements PasswordHasherPort
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function hash(string $rawPassword): string
    {
        $dummyUser = new class implements PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string
            {
                return null;
            }
        };

        return $this->hasher->hashPassword($dummyUser, $rawPassword);
    }
}
