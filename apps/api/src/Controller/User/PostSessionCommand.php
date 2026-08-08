<?php

declare(strict_types=1);

namespace App\Controller\User;

use Webmozart\Assert\Assert;

final readonly class PostSessionCommand
{
    private function __construct(
        public string $email,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $email = $payload['email'] ?? null;
        Assert::nullOrString($email, 'email must be a string or null.');

        return new self($email ?? '');
    }
}
