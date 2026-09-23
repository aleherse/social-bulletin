<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Query;

use SocialBulletin\Core\Application\Helper\BaseQuery;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Domain\User\UserId;

/**
 * Handled by {@see ShowMovementHandler}.
 *
 * @extends BaseQuery<Movement>
 */
final readonly class ShowMovementQuery extends BaseQuery
{
    public function __construct(
        public string $id,
        public UserId $authorId,
    ) {
    }
}
