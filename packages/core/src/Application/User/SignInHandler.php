<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User;

use SocialBulletin\Core\Domain\User\InvalidEmailAddress;
use SocialBulletin\Core\Domain\User\User;
use SocialBulletin\Core\Domain\User\UserId;
use SocialBulletin\Core\Domain\User\UserRepository;

final readonly class SignInHandler
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    /**
     * Signing in is what registers a user: an address nobody has used yet becomes one.
     *
     * @throws InvalidEmailAddress when the email is empty or malformed
     */
    public function __invoke(SignInCommand $command): void
    {
        $email = User::normaliseEmail($command->email);

        if (null !== $this->users->findByEmail($email)) {
            return;
        }

        $this->users->save(User::register(UserId::generate(), $email));
    }
}
