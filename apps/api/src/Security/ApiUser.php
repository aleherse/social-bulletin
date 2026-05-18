<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Webmozart\Assert\Assert;

final class ApiUser implements UserInterface
{
    /**
     * @var non-empty-string
     */
    private readonly string $email;

    public function __construct(string $email)
    {
        Assert::stringNotEmpty($email);
        $this->email = $email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }
}
