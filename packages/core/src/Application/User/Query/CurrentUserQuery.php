<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User\Query;

use SocialBulletin\Core\Application\Helper\BaseQuery;
use SocialBulletin\Core\Application\User\Model\User;

/**
 * Handled by {@see CurrentUserHandler}.
 *
 * @extends BaseQuery<User|null>
 */
final readonly class CurrentUserQuery extends BaseQuery
{
    public function __construct(
        public string $email,
    ) {
    }
}
