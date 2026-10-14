<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Helper;

interface DomainError extends \Throwable
{
    /**
     * HTTP status code apps/api answers with; implementations override it.
     */
    public const int STATUS_CODE = 500;
}
