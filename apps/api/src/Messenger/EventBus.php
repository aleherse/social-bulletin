<?php

declare(strict_types=1);

namespace App\Messenger;

use SocialBulletin\Core\Domain\Helper\DomainEvent;
use SocialBulletin\Core\Domain\Helper\EventDispatcher;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class EventBus implements EventDispatcher
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {
    }

    public function dispatch(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            try {
                $this->eventBus->dispatch($event);
            } catch (HandlerFailedException $exception) {
                throw $exception->getPrevious() ?? $exception;
            }
        }
    }
}
