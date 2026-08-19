<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\User;

use Webmozart\Assert\Assert;

final class User
{
    /**
     * Assigned by the database; only {@see self::restore()} carries it into an instance,
     * so a user that has not been added yet has none.
     */
    private \DateTimeImmutable $createdAt;

    private function __construct(
        public readonly string $id,
        public readonly string $email,
    ) {
    }

    /**
     * A user signing in for the first time.
     *
     * @throws InvalidEmailAddress when the email is empty or malformed
     */
    public static function register(string $id, string $email): self
    {
        Assert::uuid($id);

        return new self($id, self::normaliseEmail($email));
    }

    /**
     * The email as it is stored and looked up: trimmed, and rejected outright when it is not
     * one. Sign-in normalises before searching for an existing user, so the lookup and the
     * registration that may follow it agree on what the address is.
     *
     * @throws InvalidEmailAddress when the email is empty or malformed
     */
    public static function normaliseEmail(string $email): string
    {
        $email = trim($email);

        if ('' === $email) {
            throw new InvalidEmailAddress('email.blank');
        }

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddress('email.invalid');
        }

        return $email;
    }

    /**
     * Trusted hydration from persistence.
     */
    public static function restore(string $id, string $email, \DateTimeImmutable $createdAt): self
    {
        $user = new self($id, $email);
        $user->createdAt = $createdAt;

        return $user;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
