<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\User;

use SocialBulletin\Core\Domain\Helper\IdentityGenerator;
use Webmozart\Assert\Assert;

final readonly class UserService
{
    public function __construct(
        private UserRepository $users,
        private IdentityGenerator $identities,
    ) {
    }

    /**
     * @throws InvalidEmailAddress when the email is empty or malformed
     */
    public function findOrCreateByEmail(string $email): User
    {
        $email = trim($email);

        if ('' === $email) {
            throw new InvalidEmailAddress('email.blank');
        }

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddress('email.invalid');
        }

        $existing = $this->users->findByEmail($email);

        if (null !== $existing) {
            return $existing;
        }

        $id = $this->identities->generate();
        Assert::uuid($id);

        return $this->users->save(new User($id, $email));
    }

    public function currentUser(string $email): ?User
    {
        Assert::stringNotEmpty($email);

        return $this->users->findByEmail($email);
    }
}
