<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementService;
use SocialBulletin\Core\Domain\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PatchMovementController
{
    public function __construct(
        private MovementService $movementService,
    ) {
    }

    #[Route('/api/movements/{id}', name: 'api_movements_update', methods: ['PATCH'])]
    public function __invoke(string $id, Request $request, User $author): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $command = UpsertMovementCommand::fromPayload($payload);

        try {
            $movement = $this->movementService->authorMovement($id, $author->id);
            $movement = $this->movementService->update($id, $author->id, $command->toEdit($movement));
        } catch (MovementNotFound $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (MovementNotDraft $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (InvalidMovement $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(MovementPresenter::toArray($movement));
    }
}
