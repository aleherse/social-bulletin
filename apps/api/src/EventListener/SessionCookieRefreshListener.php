<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Controller\User\SessionCookie;
use App\Security\ApiUser;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Issues a fresh session cookie to an authenticated visitor whose current token has twenty minutes or less left,
 * so that an active session slides forward instead of expiring an hour after sign-in.
 */
#[AsEventListener(event: KernelEvents::RESPONSE)]
final readonly class SessionCookieRefreshListener
{
    private const MIN_REMAINING = 1200;

    public function __construct(
        private JWTTokenManagerInterface $tokenManager,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $jwt = $request->cookies->get(SessionCookie::NAME);
        $user = $this->tokenStorage->getToken()?->getUser();

        if (
            ! $event->isMainRequest()
            || ! \is_string($jwt)
            || null === $user
            || $response->isClientError()
            || $response->isServerError()
            || $this->setsSessionCookie($event)
            || ! $this->expiresSoon($jwt)
        ) {
            return;
        }

        $response->headers->setCookie(SessionCookie::create(
            $this->tokenManager->create(new ApiUser($user->getUserIdentifier())),
        ));
    }

    /**
     * A response that already sets or clears the cookie (sign-in, logout) is left as that controller made it.
     */
    private function setsSessionCookie(ResponseEvent $event): bool
    {
        foreach ($event->getResponse()->headers->getCookies() as $cookie) {
            if (SessionCookie::NAME === $cookie->getName()) {
                return true;
            }
        }

        return $event->getRequest()->attributes->has(SessionCookieListener::TOKEN_ATTRIBUTE);
    }

    private function expiresSoon(string $jwt): bool
    {
        try {
            $payload = $this->tokenManager->parse($jwt);
        } catch (JWTDecodeFailureException) {
            return false;
        }

        return isset($payload['exp']) && \is_int($payload['exp']) && $payload['exp'] - time() < self::MIN_REMAINING;
    }
}
