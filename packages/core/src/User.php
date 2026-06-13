<?php

declare(strict_types=1);

namespace SocialBulletin\Core;

use Webmozart\Assert\Assert;

final class User
{
    private string $id;
    private string $email;

    public function __construct(string $id, string $email)
    {
        Assert::uuid($id);
        Assert::notEmpty($email);

        $this->id = $id;
        $this->email = strtolower(trim($email));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }
}
