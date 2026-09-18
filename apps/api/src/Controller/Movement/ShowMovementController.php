<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Application\User\Model\User;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ShowMovementController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/movements/{id}', name: 'api_movements_show', methods: ['GET'])]
    public function __invoke(string $id, User $author): JsonResponse
    {
        try {
            $movement = $this->queryBus->dispatch(new ShowMovementQuery($id, $author->id));
        } catch (MovementNotFound $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(MovementPresenter::toArray($movement));
    }
}
