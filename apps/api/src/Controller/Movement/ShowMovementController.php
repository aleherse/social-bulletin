<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ShowMovementController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/movements/{id}', name: 'api_movements_show', methods: ['GET'])]
    public function __invoke(string $id, User $currentUser): JsonResponse
    {
        $movement = $this->queryBus->dispatch(new ShowMovementQuery($id, $currentUser->id));

        return new JsonResponse(MovementPresenter::toArray($movement));
    }
}
