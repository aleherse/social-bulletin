<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Http\JsonApiErrorResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTFailureEventInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers a missing, invalid or expired session token with a JSON:API error instead of the bundle's own body.
 */
#[AsEventListener(event: Events::JWT_NOT_FOUND)]
#[AsEventListener(event: Events::JWT_INVALID)]
#[AsEventListener(event: Events::JWT_EXPIRED)]
final readonly class AuthenticationFailureListener
{
    public function __invoke(JWTFailureEventInterface $event): void
    {
        $event->setResponse(JsonApiErrorResponse::create(Response::HTTP_UNAUTHORIZED, 'error.unauthorized'));
    }
}
