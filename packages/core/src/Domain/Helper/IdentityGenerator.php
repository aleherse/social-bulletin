<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Helper;

interface IdentityGenerator
{
    /**
     * Returns a new UUID v7 string.
     */
    public function generate(): string;
}
