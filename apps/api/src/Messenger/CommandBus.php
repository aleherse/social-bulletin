<?php

declare(strict_types=1);

namespace App\Messenger;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * ADR-0017: the only way `apps/api` reaches a `packages/core` command handler.
 *
 * Returns the handler's result — the bus is synchronous, so a controller still gets the
 * saved aggregate back — and unwraps Messenger's `HandlerFailedException` so callers
 * catch the domain exception the handler actually threw.
 */
final class CommandBus
{
    use HandleTrait;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    public function dispatch(object $command): mixed
    {
        try {
            return $this->handle($command);
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious() ?? $exception;
        }
    }
}
