<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Infrastructure\I18n;

use SocialBulletin\Api\Application\Port\TranslatorPort;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionListener implements EventSubscriberInterface
{
    public function __construct(private readonly TranslatorPort $translator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $this->translateOperationalError($statusCode);

            $event->setResponse(new JsonResponse(['error' => $message], $statusCode));

            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => 'An unexpected error occurred.'],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
    }

    private function translateOperationalError(int $statusCode): string
    {
        $key = match ($statusCode) {
            Response::HTTP_NOT_FOUND => 'error.not_found',
            Response::HTTP_BAD_REQUEST => 'error.bad_request',
            default => 'error.service_unavailable',
        };

        return $this->translator->translate($key);
    }
}
