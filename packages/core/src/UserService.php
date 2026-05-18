<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final readonly class UserService
{
    public function __construct(
        private UserRepository $users,
        private IdentityGenerator $identities,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws InvalidEmailAddress when the email is empty or malformed
     */
    public function findOrCreateByEmail(string $email): User
    {
        $email = trim($email);

        if ('' === $email) {
            throw new InvalidEmailAddress($this->translator->trans('email.blank', [], 'validators'));
        }

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddress($this->translator->trans('email.invalid', [
                'email' => $email,
            ], 'validators'));
        }

        $existing = $this->users->findByEmail($email);

        if (null !== $existing) {
            return $existing;
        }

        $id = $this->identities->generate();
        Assert::uuid($id);

        $user = new User($id, $email, new \DateTimeImmutable());
        $this->users->add($user);

        return $user;
    }

    public function currentUser(string $email): ?User
    {
        Assert::stringNotEmpty($email);

        return $this->users->findByEmail($email);
    }
}
