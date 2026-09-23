<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\User;

final class User
{
    /**
     * Assigned by the database; only {@see self::restore()} carries it into an instance,
     * so a user that has not been added yet has none.
     */
    private \DateTimeImmutable $createdAt;

    public function __construct(
        public readonly string $id,
        public readonly string $email,
    ) {
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
