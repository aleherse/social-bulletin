<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Model;

use SocialBulletin\Core\Domain\Movement\Area;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\Movement\MovementStatus;

/**
 * A movement as a reader sees it; carries no behaviour.
 * Produced by {@see \SocialBulletin\Core\Application\Movement\Provider\MovementProvider}.
 */
final readonly class Movement
{
    public function __construct(
        public MovementId $id,
        public string $title,
        public string $description,
        public string $category,
        public Area $area,
        public ?string $location,
        public MovementStatus $status,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
