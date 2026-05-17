<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Exception;

final class DuplicateEmailException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Email address "%s" is already registered.', $email));
    }
}
