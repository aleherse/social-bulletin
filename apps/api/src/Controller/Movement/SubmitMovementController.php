<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Command\SubmitMovementCommand;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SubmitMovementController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/movements/{id}/submit', name: 'api_movements_submit', methods: ['POST'])]
    public function __invoke(string $id, User $currentUser): JsonResponse
    {
        $this->commandBus->dispatch(new SubmitMovementCommand($id, $currentUser->id));
        $movement = $this->queryBus->dispatch(new ShowMovementQuery($id, $currentUser->id));

        return new JsonResponse(MovementPresenter::toArray($movement));
    }
}
