<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Event;

use SocialBulletin\Core\Application\Helper\NotificationPublisher;
use SocialBulletin\Core\Domain\Movement\Events\MovementHasBeenCreated;

final readonly class MovementCreatedHandler
{
    public function __construct(
        private NotificationPublisher $publisher,
    ) {
    }

    public function __invoke(MovementHasBeenCreated $event): void
    {
        $this->publisher->publish(new MovementChanged(
            MovementChange::Created,
            (string) $event->movementId,
            (string) $event->authorId,
        ));
    }
}
