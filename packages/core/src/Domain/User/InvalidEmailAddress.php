<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\User;

use SocialBulletin\Core\Domain\Helper\DomainError;

final class InvalidEmailAddress extends \DomainException implements DomainError
{
}
