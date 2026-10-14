<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Http\JsonApiErrorResponse;
use SocialBulletin\Core\Domain\Helper\DomainError;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Turns any domain failure into a JSON:API error response carrying the status code the exception declares.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class DomainErrorListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $error = $event->getThrowable();

        if (! $error instanceof DomainError) {
            return;
        }

        $event->setResponse(JsonApiErrorResponse::create(
            $error::STATUS_CODE,
            $error->getMessage(),
            $error instanceof InvalidMovement ? $error->errors : [],
        ));
    }
}
