<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 512)]
final readonly class OriginSecretListener
{
    public function __construct(private ?string $originSecret = null)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if ($this->originSecret === null || $this->originSecret === '') {
            return;
        }

        $request = $event->getRequest();

        if ($request->headers->get('x-socialbulletin-origin-secret') === $this->originSecret) {
            return;
        }

        $event->setResponse(new JsonResponse(['error' => 'origin_forbidden'], JsonResponse::HTTP_FORBIDDEN));
    }
}
