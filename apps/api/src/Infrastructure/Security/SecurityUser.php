<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Infrastructure\Security;

use SocialBulletin\Api\Application\Model\User;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface
{
    public function __construct(private readonly User $user)
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->user->id();
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->user->roles();
    }

    public function eraseCredentials(): void
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
