<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\CommandBus;
use SocialBulletin\Core\Application\Movement\UpdateMovementCommand;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementService;
use SocialBulletin\Core\Domain\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateMovementController
{
    public function __construct(
        private CommandBus $commandBus,
        private MovementService $movementService,
    ) {
    }

    #[Route('/api/movements/{id}', name: 'api_movements_update', methods: ['PATCH'])]
    public function __invoke(Request $request, User $author, string $id): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();

        try {
            $this->commandBus->dispatch(UpdateMovementCommand::fromPayload($payload, $author->id, $id));
            $movement = $this->movementService->authorMovement($id, $author->id);
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
