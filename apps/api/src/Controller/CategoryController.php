<?php

declare(strict_types=1);

namespace App\Controller;

use SocialBulletin\Core\Movement\Categories;
use SocialBulletin\Core\Movement\Category;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController
{
    public function __construct(
        private readonly Categories $categories,
    ) {
    }

    #[Route('/api/categories', name: 'api_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
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
