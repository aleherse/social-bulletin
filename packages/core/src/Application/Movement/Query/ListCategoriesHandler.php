<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Query;

use SocialBulletin\Core\Application\Movement\Model\Category;
use SocialBulletin\Core\Application\Movement\Provider\CategoryProvider;

final readonly class ListCategoriesHandler
{
    public function __construct(
        private CategoryProvider $categories,
    ) {
    }

    /**
     * @return list<Category> in display order
     */
    public function __invoke(ListCategoriesQuery $query): array
    {
        return $this->categories->all();
    }
}
