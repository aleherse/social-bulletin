<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\CommandBus;
use SocialBulletin\Core\Application\Movement\CreateMovementCommand;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\MovementService;
use SocialBulletin\Core\Domain\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateMovementController
{
    public function __construct(
        private CommandBus $commandBus,
        private MovementService $movementService,
    ) {
    }

    #[Route('/api/movements', name: 'api_movements_create', methods: ['POST'])]
    public function __invoke(Request $request, User $author): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $command = CreateMovementCommand::fromPayload($payload, $author->id);

        try {
            $this->commandBus->dispatch($command);
            $movement = $this->movementService->authorMovement((string) $command->id, $author->id);
        } catch (InvalidMovement $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(MovementPresenter::toArray($movement), Response::HTTP_CREATED);
    }
}
