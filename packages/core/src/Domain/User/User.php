<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\User;

use Symfony\Component\Uid\Uuid;

final class User
{
    public function __construct(
        private readonly Uuid $id,
        private readonly string $email,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromPersistence(Uuid $id, string $email, \DateTimeImmutable $createdAt): self
    {
        return new self($id, $email, $createdAt);
    }

    public static function register(string $email): self
    {
        return new self(Uuid::v7(), $email, new \DateTimeImmutable());
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
