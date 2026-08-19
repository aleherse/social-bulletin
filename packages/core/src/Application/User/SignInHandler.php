<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User;

use SocialBulletin\Core\Domain\Helper\IdentityGenerator;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;
use SocialBulletin\Core\Domain\User\User;
use SocialBulletin\Core\Domain\User\UserRepository;
use Webmozart\Assert\Assert;

final readonly class SignInHandler
{
    public function __construct(
        private UserRepository $users,
        private IdentityGenerator $identities,
    ) {
    }

    /**
     * Signing in is what registers a user: an address nobody has used yet becomes one.
     *
     * @throws InvalidEmailAddress when the email is empty or malformed
     */
    public function __invoke(SignInCommand $command): User
    {
        $email = User::normaliseEmail($command->email);
        $existing = $this->users->findByEmail($email);

        if (null !== $existing) {
            return $existing;
        }

        $id = $this->identities->generate();
        Assert::uuid($id);

        return $this->users->save(User::register($id, $email));
    }
}
