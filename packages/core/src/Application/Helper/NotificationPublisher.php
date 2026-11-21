<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Helper;

interface NotificationPublisher
{
    public function publish(Notification ...$notifications): void;
}
