<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Security\ApiUser;
use SocialBulletin\Core\Movement\MovementNotFound;
use SocialBulletin\Core\Movement\MovementService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class GetMovementController
{
    public function __construct(
        private MovementService $movementService,
        private AuthorResolver $authorResolver,
    ) {
    }

    #[Route('/api/movements/{id}', name: 'api_movements_show', methods: ['GET'])]
    public function __invoke(string $id, #[CurrentUser] ApiUser $apiUser): JsonResponse
    {
        $author = $this->authorResolver->resolve($apiUser);

        if ($author instanceof JsonResponse) {
            return $author;
        }

        try {
            $movement = $this->movementService->authorMovement($id, $author->id);
        } catch (MovementNotFound $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(MovementPresenter::toArray($movement));
    }
}
