<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Query;

use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Provider\MovementProvider;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;

final readonly class ShowMovementHandler
{
    public function __construct(
        private MovementProvider $movements,
    ) {
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     */
    public function __invoke(ShowMovementQuery $query): Movement
    {
        return $this->movements->authorMovement($query->id, $query->authorId);
    }
}
