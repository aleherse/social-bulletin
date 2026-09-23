<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Model\Category;
use SocialBulletin\Core\Application\Movement\Query\ListCategoriesQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListCategoriesController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/categories', name: 'api_categories_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'categories' => array_map(
                static fn (Category $category): array => [
                    'id' => $category->id,
                ],
                $this->queryBus->dispatch(new ListCategoriesQuery()),
            ),
        ]);
    }
}
