<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Query;

use SocialBulletin\Core\Application\Helper\BaseQuery;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Domain\User\UserId;

/**
 * Handled by {@see ListMovementsHandler}.
 *
 * @extends BaseQuery<list<Movement>>
 */
final readonly class ListMovementsQuery extends BaseQuery
{
    public function __construct(
        public UserId $authorId,
    ) {
    }
}
