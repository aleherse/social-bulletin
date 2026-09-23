<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

/**
 * Intent to move a `draft` movement on to `proposed`.
 */
final readonly class SubmitMovement implements MovementCommand
{
}
