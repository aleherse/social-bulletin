<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use SocialBulletin\Core\Movement\InvalidMovement;
use SocialBulletin\Core\Movement\MovementService;
use SocialBulletin\Core\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PostMovementController
{
    public function __construct(
        private MovementService $movementService,
    ) {
    }

    #[Route('/api/movements', name: 'api_movements_create', methods: ['POST'])]
    public function __invoke(Request $request, User $author): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $command = UpsertMovementCommand::fromPayload($payload);

        try {
            $movement = $this->movementService->create(
                $author->id,
                $command->title ?? '',
                $command->description ?? '',
                $command->category ?? '',
                $command->area ?? '',
                $command->location,
            );
        } catch (InvalidMovement $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(MovementPresenter::toArray($movement), Response::HTTP_CREATED);
    }
}
