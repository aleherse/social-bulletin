<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class User
{
    public function __construct(
        public string $id,
        public string $email,
    ) {
        Assert::uuid($id);
        Assert::email($email);
    }

    public static function register(string $email): self
    {
        return new self((string) Uuid::v7(), strtolower(trim($email)));
    }
}
