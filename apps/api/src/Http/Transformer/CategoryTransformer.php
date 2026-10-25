<?php

declare(strict_types=1);

namespace App\Http\Transformer;

use League\Fractal\TransformerAbstract;
use SocialBulletin\Core\Application\Movement\Model\Category;

final class CategoryTransformer extends TransformerAbstract
{
    /**
     * @return array{id: string}
     */
    public function transform(Category $category): array
    {
        return [
            'id' => $category->id,
        ];
    }
}
