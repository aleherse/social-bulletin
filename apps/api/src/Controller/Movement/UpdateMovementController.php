<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Http\Attribute\JsonApi;
use App\Http\Attribute\JsonApiPayload;
use App\Http\Transformer\MovementTransformer;
use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Command\UpdateMovementCommand;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateMovementController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[JsonApi(MovementTransformer::class, 'movements')]
    #[Route('/api/movements/{id}', name: 'api_movements_update', methods: ['PATCH'])]
    public function __invoke(User $currentUser, #[JsonApiPayload('movements', UpdateMovementCommand::class)] array $payload, string $id): Movement
    {
        $this->commandBus->dispatch(UpdateMovementCommand::fromPayload($payload, $currentUser->id, $id));

        return $this->queryBus->dispatch(new ShowMovementQuery($id, $currentUser->id));
    }
}
