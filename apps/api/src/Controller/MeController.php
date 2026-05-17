<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Auth')]
final class MeController
{
    public function __construct(private readonly Security $security)
    {
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/me',
        summary: 'Get current authenticated user',
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();

        if ($user === null) {
            return new JsonResponse(['error' => 'unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['userId' => $user->getUserIdentifier()]);
    }
}
