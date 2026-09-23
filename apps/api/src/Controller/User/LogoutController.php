<?php

declare(strict_types=1);

namespace App\Controller\User;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LogoutController
{
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $response->headers->clearCookie(
            SessionCookie::NAME,
            '/',
            null,
            true,
            true,
            Cookie::SAMESITE_STRICT,
        );

        return $response;
    }
}
