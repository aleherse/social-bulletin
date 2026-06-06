<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Controller;

use OpenApi\Attributes as OA;
use SocialBulletin\Api\Security\ApiUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CurrentUserController extends AbstractController
{
    #[OA\Get(
        path: '/auth/me',
        summary: 'Return the current authenticated user from the JWT cookie.',
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user email returned.'),
            new OA\Response(response: 401, description: 'Authentication required.'),
        ],
    )]
    #[Route('/auth/me', name: 'auth_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?ApiUser $user): JsonResponse
    {
        if ($user === null) {
            return new JsonResponse(['error' => 'authentication_required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['email' => $user->domainUser()->emailAddress()->value()]);
    }
}
