<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Place;

/**
 * Raised while importing places; it reaches the console, never an HTTP response.
 */
final class InvalidPlaceFeed extends \DomainException
{
    public static function atLine(int $line, string $reason): self
    {
        return new self(sprintf('Invalid place feature on line %d: %s.', $line, $reason));
    }
}
