<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Application\Helper\Command;
use SocialBulletin\Core\Domain\User\UserId;

/**
 * Command: move the author's `draft` movement on to `proposed`.
 *
 * Every field is required, so all of them are promoted and always initialised.
 * Handled by {@see SubmitMovementHandler}.
 */
final readonly class SubmitMovementCommand extends Command
{
    public function __construct(
        public string $id,
        public UserId $authorId,
    ) {
    }
}
