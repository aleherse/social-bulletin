<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Http\Attribute\JsonApi;
use App\Http\Transformer\CategoryTransformer;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Movement\Model\Category;
use SocialBulletin\Core\Application\Movement\Query\ListCategoriesQuery;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListCategoriesController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    /**
     * @return list<Category>
     */
    #[JsonApi(CategoryTransformer::class, 'categories')]
    #[Route('/api/categories', name: 'api_categories_list', methods: ['GET'])]
    public function __invoke(): array
    {
        return $this->queryBus->dispatch(new ListCategoriesQuery());
    }
}
