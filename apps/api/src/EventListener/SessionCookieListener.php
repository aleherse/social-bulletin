<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Controller\User\SessionCookie;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the session cookie for a controller that left its token on the request, so that it can return a model
 * for `JsonApiViewListener` to render instead of building the response itself.
 */
#[AsEventListener(event: KernelEvents::RESPONSE)]
final readonly class SessionCookieListener
{
    public const TOKEN_ATTRIBUTE = '_session_token';

    public function __invoke(ResponseEvent $event): void
    {
        $token = $event->getRequest()
            ->attributes->get(self::TOKEN_ATTRIBUTE);

        if (\is_string($token)) {
            $event->getResponse()
                ->headers->setCookie(SessionCookie::create($token));
        }
    }
}
