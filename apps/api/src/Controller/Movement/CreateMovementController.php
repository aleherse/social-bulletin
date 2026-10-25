<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Http\Attribute\JsonApi;
use App\Http\Attribute\JsonApiPayload;
use App\Http\Transformer\MovementTransformer;
use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Command\CreateMovementCommand;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateMovementController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[JsonApi(MovementTransformer::class, 'movements', status: Response::HTTP_CREATED)]
    #[Route('/api/movements', name: 'api_movements_create', methods: ['POST'])]
    public function __invoke(User $currentUser, #[JsonApiPayload('movements', CreateMovementCommand::class)] array $payload): Movement
    {
        $command = CreateMovementCommand::fromPayload($payload, $currentUser->id);

        $this->commandBus->dispatch($command);

        return $this->queryBus->dispatch(new ShowMovementQuery((string) $command->id, $currentUser->id));
    }
}
