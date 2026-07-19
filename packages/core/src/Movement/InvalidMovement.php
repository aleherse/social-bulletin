<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

final class InvalidMovement extends \DomainException
{
    /**
     * @param array<string, string> $errors field name => translated message
     */
    public function __construct(
        public readonly array $errors,
        string $message,
    ) {
        parent::__construct($message);
    }
}
