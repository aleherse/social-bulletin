<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\ValueObject;

final class EmailAddress
{
    private readonly string $value;

    public function __construct(string $email)
    {
        $normalised = strtolower($email);

        if (!filter_var($normalised, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid email address.', $email)
            );
        }

        $this->value = $normalised;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
