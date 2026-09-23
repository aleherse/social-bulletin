<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User\Query;

use SocialBulletin\Core\Application\User\Model\User;
use SocialBulletin\Core\Application\User\Provider\UserProvider;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;
use SocialBulletin\Core\Domain\User\User as UserAggregate;

final readonly class CurrentUserHandler
{
    public function __construct(
        private UserProvider $users,
    ) {
    }

    /**
     * @throws InvalidEmailAddress when the email is empty or malformed
     */
    public function __invoke(CurrentUserQuery $query): ?User
    {
        return $this->users->currentUser(UserAggregate::normaliseEmail($query->email));
    }
}
