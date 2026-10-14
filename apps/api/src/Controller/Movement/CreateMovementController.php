<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Command\CreateMovementCommand;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateMovementController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/movements', name: 'api_movements_create', methods: ['POST'])]
    public function __invoke(Request $request, User $currentUser): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $command = CreateMovementCommand::fromPayload($payload, $currentUser->id);

        $this->commandBus->dispatch($command);
        $movement = $this->queryBus->dispatch(
            new ShowMovementQuery((string) $command->id, $currentUser->id),
        );

        return new JsonResponse(MovementPresenter::toArray($movement), Response::HTTP_CREATED);
    }
}
