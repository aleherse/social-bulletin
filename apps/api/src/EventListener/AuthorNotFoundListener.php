<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\AuthorNotFound;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class AuthorNotFoundListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (! $event->getThrowable() instanceof AuthorNotFound) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'message' => 'error.unauthorized',
        ], Response::HTTP_UNAUTHORIZED));
    }
}
