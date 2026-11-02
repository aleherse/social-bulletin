<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Event;

use SocialBulletin\Core\Application\Helper\NotificationPublisher;
use SocialBulletin\Core\Domain\Movement\Events\MovementHasBeenSubmitted;

final readonly class MovementSubmittedHandler
{
    public function __construct(
        private NotificationPublisher $publisher,
    ) {
    }

    public function __invoke(MovementHasBeenSubmitted $event): void
    {
        $this->publisher->publish(new MovementChanged(
            MovementChange::Submitted,
            (string) $event->movementId,
            (string) $event->authorId,
        ));
    }
}
