<?php

declare(strict_types=1);

namespace SocialBulletin\Core\User;

final readonly class User
{
    public function __construct(
        private string $id,
        private EmailAddress $emailAddress,
    ) {
        if ($id === '') {
            throw new \InvalidArgumentException('User id is required.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }
}
