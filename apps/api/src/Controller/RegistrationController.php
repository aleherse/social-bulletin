<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use SocialBulletin\Api\Persistence\DbalUserRepository;
use SocialBulletin\Api\Security\ApiUser;
use SocialBulletin\Core\User\EmailAddress;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[OA\Post(
        path: '/auth/register',
        summary: 'Create or authenticate a user by email.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ['email'], properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user email returned and JWT cookie set.'),
            new OA\Response(response: 400, description: 'Invalid email payload.'),
        ],
    )]
    #[Route('/auth/register', name: 'auth_register', methods: ['POST'])]
    public function __invoke(
        Request $request,
        DbalUserRepository $users,
        JWTTokenManagerInterface $jwtTokenManager,
    ): JsonResponse {
        try {
            /** @var array{email?: mixed} $payload */
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $emailAddress = new EmailAddress(is_string($payload['email'] ?? null) ? $payload['email'] : '');
        } catch (\JsonException|\InvalidArgumentException) {
            return new JsonResponse(['error' => 'invalid_email'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $users->findByEmail($emailAddress) ?? $users->create($emailAddress);
        $token = $jwtTokenManager->create(new ApiUser($user));

        $response = new JsonResponse(['email' => $user->emailAddress()->value()]);
        $response->headers->setCookie(Cookie::create('token', $token, 0, '/', null, true, true, false, Cookie::SAMESITE_STRICT));

        return $response;
    }
}
