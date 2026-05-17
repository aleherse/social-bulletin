<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Command;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $rawPassword,
        public bool $termsAccepted,
    ) {
    }
}
