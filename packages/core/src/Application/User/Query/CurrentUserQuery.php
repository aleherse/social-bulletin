<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User\Query;

use SocialBulletin\Core\Application\Helper\BaseQuery;
use SocialBulletin\Core\Application\User\Model\User;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;
use SocialBulletin\Core\Domain\User\User as UserAggregate;

/**
 * @extends BaseQuery<User|null>
 */
final readonly class CurrentUserQuery extends BaseQuery
{
    public string $email;

    /**
     * @throws InvalidEmailAddress when the email is empty or malformed
     */
    public function __construct(string $email)
    {
        $this->email = UserAggregate::normaliseEmail($email);
    }
}
