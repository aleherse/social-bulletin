<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Place;

use SocialBulletin\Core\Domain\Helper\DomainError;

final class InvalidPlaceLookup extends \DomainException implements DomainError
{
    public const int STATUS_CODE = 422;

    /**
     * @param array<string, string> $errors field name => translation key
     */
    public function __construct(
        public readonly array $errors,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
