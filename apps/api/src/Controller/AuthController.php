<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\JwtUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use SocialBulletin\Core\Application\User\RegisterOrLoginUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;

#[Route('/api')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly RegisterOrLoginUser $registerOrLogin,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        /** @var array{email?: string} $payload */
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::keyExists($payload, 'email');
        Assert::email($payload['email']);

        $user = $this->registerOrLogin->execute($payload['email']);
        $jwtUser = new JwtUser($user->id()->toRfc4122(), $user->email());
        $token = $this->jwtManager->createFromPayload($jwtUser, [
            'id' => $user->id()->toRfc4122(),
            'email' => $user->email(),
        ]);

        $response = new JsonResponse(['email' => $user->email()], Response::HTTP_OK);
        $response->headers->setCookie($this->createTokenCookie($token, time() + 3600));

        return $response;
    }

    #[Route('/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof JwtUser) {
            return new JsonResponse(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['email' => $user->email()]);
    }

    #[Route('/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $response->headers->clearCookie('token', '/', null, true, true, Cookie::SAMESITE_STRICT);

        return $response;
    }

    private function createTokenCookie(string $token, int $expires): Cookie
    {
        return Cookie::create('token')
            ->withValue($token)
            ->withExpires($expires)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }
}
