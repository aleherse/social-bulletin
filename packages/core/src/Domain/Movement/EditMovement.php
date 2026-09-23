<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

/**
 * Intent to replace every editable field of a `draft` movement.
 */
final readonly class EditMovement implements MovementCommand
{
    public function __construct(
        public string $title,
        public string $description,
        public string $category,
        public string $area,
        public ?string $location,
    ) {
    }
}
