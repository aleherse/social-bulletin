<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
final class LogoutController
{
    public function __invoke(): Response
    {
        $cookie = Cookie::create('token')
            ->withValue('')
            ->withExpires(new \DateTimeImmutable('1970-01-01'))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);

        $response = new JsonResponse(['message' => 'Logged out']);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
