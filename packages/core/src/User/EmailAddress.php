<?php

declare(strict_types=1);

namespace SocialBulletin\Core\User;

final readonly class EmailAddress
{
    private string $value;

    public function __construct(string $value)
    {
        $normalised = strtolower(trim($value));

        if (filter_var($normalised, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Email address is invalid.');
        }

        if (strlen($normalised) > 320) {
            throw new \InvalidArgumentException('Email address is too long.');
        }

        $this->value = $normalised;
    }

    public function value(): string
    {
        return $this->value;
    }
}
