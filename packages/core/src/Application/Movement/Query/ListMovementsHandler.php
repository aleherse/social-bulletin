<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Query;

use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Provider\MovementProvider;

final readonly class ListMovementsHandler
{
    public function __construct(
        private MovementProvider $movements,
    ) {
    }

    /**
     * @return list<Movement> newest first
     */
    public function __invoke(ListMovementsQuery $query): array
    {
        return $this->movements->byAuthor($query->authorId);
    }
}
