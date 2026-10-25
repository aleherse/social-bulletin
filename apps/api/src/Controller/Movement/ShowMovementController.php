<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Http\Attribute\JsonApi;
use App\Http\Transformer\MovementTransformer;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ShowMovementController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    #[JsonApi(MovementTransformer::class, 'movements')]
    #[Route('/api/movements/{id}', name: 'api_movements_show', methods: ['GET'])]
    public function __invoke(string $id, User $currentUser): Movement
    {
        return $this->queryBus->dispatch(new ShowMovementQuery($id, $currentUser->id));
    }
}
