<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\User\Query;

use SocialBulletin\Core\Application\User\Model\User;
use SocialBulletin\Core\Application\User\Provider\UserProvider;

final readonly class CurrentUserHandler
{
    public function __construct(
        private UserProvider $users,
    ) {
    }

    public function __invoke(CurrentUserQuery $query): ?User
    {
        return $this->users->byEmail($query->email);
    }
}
