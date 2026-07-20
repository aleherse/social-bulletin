<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\ApiUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use SocialBulletin\Core\User\InvalidEmailAddress;
use SocialBulletin\Core\User\UserService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SessionController
{
    private const COOKIE_NAME = 'token';
    private const COOKIE_LIFETIME = 3600;

    public function __construct(
        private readonly UserService $userService,
        private readonly JWTTokenManagerInterface $tokenManager,
    ) {
    }

    #[Route('/api/session', name: 'api_session_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var array{email?: mixed} $payload */
        $payload = $request->toArray();
        $email = \is_string($payload['email'] ?? null) ? $payload['email'] : '';

        try {
            $user = $this->userService->findOrCreateByEmail($email);
        } catch (InvalidEmailAddress $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $jwt = $this->tokenManager->create(new ApiUser($user->email));

        $response = new JsonResponse([
            'email' => $user->email,
        ]);
        $response->headers->setCookie($this->sessionCookie($jwt));

        return $response;
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ApiUser $user): JsonResponse
    {
        return new JsonResponse([
            'email' => $user->getUserIdentifier(),
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $response->headers->clearCookie(
            self::COOKIE_NAME,
            '/',
            null,
            true,
            true,
            Cookie::SAMESITE_STRICT,
        );

        return $response;
    }

    private function sessionCookie(string $jwt): Cookie
    {
        // ADR-0011: the JWT travels only in an httpOnly cookie.
        return Cookie::create(
            self::COOKIE_NAME,
            $jwt,
            time() + self::COOKIE_LIFETIME,
            '/',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_STRICT,
        );
    }
}
