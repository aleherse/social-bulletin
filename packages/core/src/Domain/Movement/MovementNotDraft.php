<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

use SocialBulletin\Core\Domain\Helper\DomainError;

final class MovementNotDraft extends \DomainException implements DomainError
{
}
