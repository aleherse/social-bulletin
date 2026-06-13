<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use SocialBulletin\Api\Security\AuthenticatedUser;
use SocialBulletin\Core\CreateOrAuthenticateUser;
use SocialBulletin\Core\InvalidEmailAddress;
use SocialBulletin\Core\LogoutUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AuthController extends AbstractController
{
    #[Route('/api/auth/register-or-login', name: 'api_auth_register_or_login', methods: ['POST'])]
    public function registerOrLogin(
        Request $request,
        CreateOrAuthenticateUser $createOrAuthenticateUser,
        JWTTokenManagerInterface $jwt,
        TranslatorInterface $translator,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['message' => $translator->trans('invalid_json', [], 'errors')], 400);
        }

        $email = $payload['email'] ?? null;
        if (!is_string($email) || trim($email) === '') {
            return new JsonResponse(['message' => $translator->trans('invalid_email', [], 'errors')], 400);
        }

        try {
            $user = $createOrAuthenticateUser($email);
        } catch (InvalidEmailAddress $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 400);
        }

        $authenticatedUser = new AuthenticatedUser($user);
        $token = $jwt->createFromPayload($authenticatedUser, ['email' => $user->email]);
        $response = new JsonResponse(['user' => ['email' => $user->email]]);
        $response->headers->setCookie($this->createTokenCookie($token));

        return $response;
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(TranslatorInterface $translator): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof AuthenticatedUser) {
            return new JsonResponse(['message' => $translator->trans('authentication_required', [], 'errors')], 401);
        }

        return new JsonResponse(['user' => ['email' => $user->email()]]);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(LogoutUser $logoutUser): JsonResponse
    {
        $intent = $logoutUser();
        $response = new JsonResponse(null, 204);
        $response->headers->clearCookie($intent->cookieName, '/', null, true, true, Cookie::SAMESITE_STRICT);

        return $response;
    }

    private function createTokenCookie(string $token): Cookie
    {
        return Cookie::create('token')
            ->withValue($token)
            ->withExpires(strtotime('+1 hour'))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }
}
