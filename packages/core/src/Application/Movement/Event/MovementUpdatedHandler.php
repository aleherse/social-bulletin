<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Event;

use SocialBulletin\Core\Application\Helper\NotificationPublisher;
use SocialBulletin\Core\Domain\Movement\Events\MovementHasBeenUpdated;

final readonly class MovementUpdatedHandler
{
    public function __construct(
        private NotificationPublisher $publisher,
    ) {
    }

    public function __invoke(MovementHasBeenUpdated $event): void
    {
        $this->publisher->publish(new MovementChanged(
            MovementChange::Updated,
            (string) $event->movementId,
            (string) $event->authorId,
        ));
    }
}
