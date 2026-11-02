<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Helper;

interface Notification
{
    public function channel(): string;

    /**
     * @return array<string, string>
     */
    public function payload(): array;
}
