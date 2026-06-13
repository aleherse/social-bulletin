<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/me', name: 'api_me', methods: ['GET'])]
final class MeController
{
    public function __invoke(#[CurrentUser] User $user): Response
    {
        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }
}
