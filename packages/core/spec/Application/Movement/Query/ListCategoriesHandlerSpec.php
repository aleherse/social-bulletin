<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement\Query;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Application\Movement\Model\Category;
use SocialBulletin\Core\Application\Movement\Provider\CategoryProvider;
use SocialBulletin\Core\Application\Movement\Query\ListCategoriesQuery;

final class ListCategoriesHandlerSpec extends ObjectBehavior
{
    public function let(CategoryProvider $categories): void
    {
        $this->beConstructedWith($categories);
    }

    public function it_lists_the_categories_in_the_order_the_provider_gives_them(
        CategoryProvider $categories,
    ): void {
        $animalRights = new Category('animal_rights');
        $cooperative = new Category('cooperative');
        $categories->all()->willReturn([$animalRights, $cooperative]);

        $this->__invoke(new ListCategoriesQuery())->shouldReturn([$animalRights, $cooperative]);
    }
}
