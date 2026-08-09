<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

/**
 * Intent to start a movement as a `draft`.
 */
final readonly class DraftMovement
{
    public function __construct(
        public string $authorId,
        public string $title,
        public string $description,
        public string $category,
        public string $area,
        public ?string $location,
    ) {
    }
}
