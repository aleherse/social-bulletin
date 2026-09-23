<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Helper;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @phpstan-consistent-constructor
 */
abstract class AggregateId implements \Stringable
{
    protected function __construct(
        private readonly string $value,
    ) {
    }

    public static function from(string $value): static
    {
        Assert::uuid($value);

        return new static($value);
    }

    public static function generate(): static
    {
        return new static(Uuid::v7()->toRfc4122());
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
