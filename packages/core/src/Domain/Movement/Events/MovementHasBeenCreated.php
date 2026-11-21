<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement\Events;

use SocialBulletin\Core\Domain\Helper\DomainEvent;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\User\UserId;

final readonly class MovementHasBeenCreated implements DomainEvent
{
    public function __construct(
        public MovementId $movementId,
        public UserId $authorId,
    ) {
    }
}
