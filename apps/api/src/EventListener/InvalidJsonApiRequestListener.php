<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Http\InvalidJsonApiRequest;
use App\Http\JsonApiErrorResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class InvalidJsonApiRequestListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $error = $event->getThrowable();

        if (! $error instanceof InvalidJsonApiRequest) {
            return;
        }

        $event->setResponse(JsonApiErrorResponse::create($error->status, $error->getMessage()));
    }
}
