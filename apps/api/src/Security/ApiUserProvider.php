<?php

declare(strict_types=1);

namespace App\Security;

use SocialBulletin\Core\User\UserService;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<ApiUser>
 */
final class ApiUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userService->currentUser($identifier);

        if (null === $user) {
            throw new UserNotFoundException(sprintf('No user found for email "%s".', $identifier));
        }

        return new ApiUser($user->email);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return ApiUser::class === $class;
    }
}
