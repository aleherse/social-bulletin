<?php

declare(strict_types=1);

namespace App\Http\Transformer;

use League\Fractal\TransformerAbstract;
use SocialBulletin\Core\Application\Movement\Model\Movement;

final class MovementTransformer extends TransformerAbstract
{
    /**
     * @return array<string, string|null>
     */
    public function transform(Movement $movement): array
    {
        return [
            'id' => $movement->id,
            'title' => $movement->title,
            'description' => $movement->description,
            'category' => $movement->category,
            'area' => $movement->area,
            'location' => $movement->location,
            'status' => $movement->status,
            'createdAt' => $movement->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $movement->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
