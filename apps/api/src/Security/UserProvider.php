<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Security;

use SocialBulletin\Api\Persistence\DbalUserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/** @implements UserProviderInterface<ApiUser> */
final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(private DbalUserRepository $users)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findById($identifier);

        if ($user === null) {
            throw new UserNotFoundException('User not found.');
        }

        return new ApiUser($user);
    }

    public function refreshUser(UserInterface $user): ApiUser
    {
        if (!$user instanceof ApiUser) {
            throw new UserNotFoundException('Unsupported user class.');
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return is_a($class, ApiUser::class, true);
    }
}
