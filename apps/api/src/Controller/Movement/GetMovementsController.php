<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Security\ApiUser;
use SocialBulletin\Core\Movement\MovementService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class GetMovementsController
{
    public function __construct(
        private MovementService $movementService,
        private AuthorResolver $authorResolver,
    ) {
    }

    #[Route('/api/movements', name: 'api_movements_list', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ApiUser $apiUser): JsonResponse
    {
        $author = $this->authorResolver->resolve($apiUser);

        if ($author instanceof JsonResponse) {
            return $author;
        }

        return new JsonResponse([
            'movements' => array_map(
                MovementPresenter::toArray(...),
                $this->movementService->byAuthor($author->id),
            ),
        ]);
    }
}
