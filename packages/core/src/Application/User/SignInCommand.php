<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User;

use SocialBulletin\Core\Application\Helper\Command;
use Webmozart\Assert\Assert;

/**
 * Command: open a session for an email address, registering the user the first time.
 *
 * The email is the whole input and is always initialised — a payload that omits it carries an
 * empty address, which the domain then rejects like any other unusable one.
 * Handled by {@see SignInHandler}.
 */
final readonly class SignInCommand extends Command
{
    public string $email;

    /**
     * Validates shape only; whether the address is a usable one is the domain's call.
     *
     * @param array<string, mixed> $payload
     */
    private function __construct(array $payload)
    {
        $email = $payload['email'] ?? null;
        Assert::nullOrString($email, 'email must be a string or null.');
        $this->email = $email ?? '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self($payload);
    }
}
