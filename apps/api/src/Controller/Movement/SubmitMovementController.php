<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Http\Attribute\JsonApi;
use App\Http\Transformer\MovementTransformer;
use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Command\SubmitMovementCommand;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SubmitMovementController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {
    }

    #[JsonApi(MovementTransformer::class, 'movements')]
    #[Route('/api/movements/{id}/submit', name: 'api_movements_submit', methods: ['POST'])]
    public function __invoke(string $id, User $currentUser): Movement
    {
        $this->commandBus->dispatch(new SubmitMovementCommand($id, $currentUser->id));

        return $this->queryBus->dispatch(new ShowMovementQuery($id, $currentUser->id));
    }
}
