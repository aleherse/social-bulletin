<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

use Webmozart\Assert\Assert;

final class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $normalised = strtolower(trim($value));
        Assert::email($normalised, 'Invalid email address: %s');

        $this->value = $normalised;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
