<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

/**
 * An intent to change an existing movement, handled by {@see Movement::apply()}.
 *
 * Implemented only by EditMovement and SubmitMovement; creation is expressed by
 * DraftMovement, which {@see Movement::draft()} consumes instead.
 */
interface MovementCommand
{
}
