<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Command\UpdateMovementCommand;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateMovementController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/movements/{id}', name: 'api_movements_update', methods: ['PATCH'])]
    public function __invoke(Request $request, User $currentUser, string $id): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();

        $this->commandBus->dispatch(UpdateMovementCommand::fromPayload($payload, $currentUser->id, $id));
        $movement = $this->queryBus->dispatch(new ShowMovementQuery($id, $currentUser->id));

        return new JsonResponse(MovementPresenter::toArray($movement));
    }
}
