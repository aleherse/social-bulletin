<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Http\Attribute\JsonApi;
use App\Http\Transformer\MovementTransformer;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Query\ListMovementsQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListMovementsController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    /**
     * @return list<Movement>
     */
    #[JsonApi(MovementTransformer::class, 'movements')]
    #[Route('/api/movements', name: 'api_movements_list', methods: ['GET'])]
    public function __invoke(User $currentUser): array
    {
        return $this->queryBus->dispatch(new ListMovementsQuery($currentUser->id));
    }
}
