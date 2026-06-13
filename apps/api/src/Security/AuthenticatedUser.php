<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Security;

use SocialBulletin\Core\User;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class AuthenticatedUser implements UserInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->user->id;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function email(): string
    {
        return $this->user->email;
    }

    public function coreUser(): User
    {
        return $this->user;
    }
}
