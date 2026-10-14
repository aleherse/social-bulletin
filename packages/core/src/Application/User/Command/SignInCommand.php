<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User\Command;

use SocialBulletin\Core\Application\Helper\BaseCommand;
use Webmozart\Assert\Assert;

final readonly class SignInCommand extends BaseCommand
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
