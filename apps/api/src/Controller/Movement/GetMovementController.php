<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementService;
use SocialBulletin\Core\Domain\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetMovementController
{
    public function __construct(
        private MovementService $movementService,
    ) {
    }

    #[Route('/api/movements/{id}', name: 'api_movements_show', methods: ['GET'])]
    public function __invoke(string $id, User $author): JsonResponse
    {
        try {
            $movement = $this->movementService->authorMovement($id, $author->id);
        } catch (MovementNotFound $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(MovementPresenter::toArray($movement));
    }
}
