<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use SocialBulletin\Core\Domain\Movement\MovementService;
use SocialBulletin\Core\Domain\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListMovementsController
{
    public function __construct(
        private MovementService $movementService,
    ) {
    }

    #[Route('/api/movements', name: 'api_movements_list', methods: ['GET'])]
    public function __invoke(User $author): JsonResponse
    {
        return new JsonResponse([
            'movements' => array_map(
                MovementPresenter::toArray(...),
                $this->movementService->byAuthor($author->id),
            ),
        ]);
    }
}
