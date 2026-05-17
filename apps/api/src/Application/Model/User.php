<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Model;

use SocialBulletin\Api\Application\ValueObject\EmailAddress;

final class User
{
    /** @param non-empty-list<string> $roles */
    public function __construct(
        private readonly string $id,
        private readonly EmailAddress $email,
        private readonly string $passwordHash,
        private readonly \DateTimeImmutable $termsAcceptedAt,
        private readonly \DateTimeImmutable $registeredAt,
        private readonly array $roles = ['ROLE_USER'],
    ) {
        if (empty($this->roles)) {
            throw new \InvalidArgumentException('A user must have at least one role.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    /** @return non-empty-list<string> */
    public function roles(): array
    {
        return $this->roles;
    }

    public function termsAcceptedAt(): \DateTimeImmutable
    {
        return $this->termsAcceptedAt;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
