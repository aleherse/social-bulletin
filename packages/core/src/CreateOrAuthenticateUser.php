<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final readonly class CreateOrAuthenticateUser
{
    public function __construct(
        private UserRepository $users,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(string $email): User
    {
        $normalisedEmail = strtolower(trim($email));

        try {
            Assert::email($normalisedEmail);
        } catch (\InvalidArgumentException $exception) {
            $message = $this->translator->trans('email_invalid', [], 'validators');

            throw new InvalidEmailAddress($message, previous: $exception);
        }

        $existingUser = $this->users->findByEmail($normalisedEmail);
        if ($existingUser instanceof User) {
            return $existingUser;
        }

        $user = User::register($normalisedEmail);
        $this->users->save($user);

        return $user;
    }
}
