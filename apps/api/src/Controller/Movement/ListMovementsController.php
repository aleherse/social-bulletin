<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Query\ListMovementsQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListMovementsController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/movements', name: 'api_movements_list', methods: ['GET'])]
    public function __invoke(User $author): JsonResponse
    {
        return new JsonResponse([
            'movements' => array_map(
                MovementPresenter::toArray(...),
                $this->queryBus->dispatch(new ListMovementsQuery($author->id)),
            ),
        ]);
    }
}
