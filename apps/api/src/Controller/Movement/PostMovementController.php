<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Controller\RequestPayload;
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

        try {
            $movement = $this->movementService->create(
                $author->id,
                RequestPayload::stringField($payload, 'title'),
                RequestPayload::stringField($payload, 'description'),
                RequestPayload::stringField($payload, 'category'),
                RequestPayload::stringField($payload, 'area'),
                RequestPayload::nullableStringField($payload, 'location'),
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
