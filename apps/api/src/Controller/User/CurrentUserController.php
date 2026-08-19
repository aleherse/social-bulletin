<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Security\ApiUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CurrentUserController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ApiUser $user): JsonResponse
    {
        return new JsonResponse([
            'email' => $user->getUserIdentifier(),
        ]);
    }
}
