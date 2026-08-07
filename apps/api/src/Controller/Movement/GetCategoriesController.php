<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use SocialBulletin\Core\Movement\Category;
use SocialBulletin\Core\Movement\CategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetCategoriesController
{
    public function __construct(
        private CategoryRepository $categories,
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
                $this->categories->all(),
            ),
        ]);
    }
}
