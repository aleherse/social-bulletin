<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;

final class JwtUser implements JWTUserInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
    ) {
    }

    public static function createFromPayload($username, array $payload): self
    {
        return new self(
            (string) ($payload['id'] ?? $username),
            (string) ($payload['email'] ?? $username),
        );
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }
}
