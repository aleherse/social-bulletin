<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Security;

use SocialBulletin\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class ApiUser implements UserInterface
{
    public function __construct(private User $user)
    {
    }

    public function domainUser(): User
    {
        return $this->user;
    }

    public function getUserIdentifier(): string
    {
        return $this->user->id();
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }
}
