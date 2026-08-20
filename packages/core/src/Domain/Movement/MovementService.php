<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

use SocialBulletin\Core\Domain\User\UserId;

/**
 * Reads movements. Writes go through the commands and handlers in
 * {@see \SocialBulletin\Core\Application\Movement}.
 */
final readonly class MovementService
{
    public function __construct(
        private MovementRepository $movements,
    ) {
    }

    /**
     * @return list<Movement> newest first
     */
    public function byAuthor(UserId $authorId): array
    {
        return $this->movements->byAuthor($authorId);
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     */
    public function authorMovement(string $id, UserId $authorId): Movement
    {
        $movement = $this->movements->byId($id);

        if (null === $movement || ! $movement->authorId->equals($authorId)) {
            throw new MovementNotFound('movement.not_found');
        }

        return $movement;
    }
}
