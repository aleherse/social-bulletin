<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Security;

use SocialBulletin\Core\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<AuthenticatedUser>
 */
final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findById($identifier);
        if ($user === null) {
            throw new UserNotFoundException(sprintf('User "%s" was not found.', $identifier));
        }

        return new AuthenticatedUser($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof AuthenticatedUser) {
            throw new UserNotFoundException('Unsupported user type.');
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === AuthenticatedUser::class;
    }
}
