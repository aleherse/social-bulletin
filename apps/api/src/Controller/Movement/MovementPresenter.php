<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use SocialBulletin\Core\Domain\Movement\Movement;

final class MovementPresenter
{
    private function __construct()
    {
    }

    /**
     * @return array<string, string|null>
     */
    public static function toArray(Movement $movement): array
    {
        return [
            'id' => $movement->id,
            'title' => $movement->title(),
            'description' => $movement->description(),
            'category' => $movement->category(),
            'area' => $movement->area()
                ->value,
            'location' => $movement->location(),
            'status' => $movement->status()
                ->value,
            'createdAt' => $movement->createdAt()
                ->format(\DateTimeInterface::ATOM),
            'updatedAt' => $movement->updatedAt()
                ->format(\DateTimeInterface::ATOM),
        ];
    }
}
