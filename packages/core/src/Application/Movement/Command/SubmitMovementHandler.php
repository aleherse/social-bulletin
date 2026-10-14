<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Command;

use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementRepository;

final readonly class SubmitMovementHandler
{
    public function __construct(
        private MovementRepository $movements,
    ) {
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when the description is still empty
     */
    public function __invoke(SubmitMovementCommand $command): void
    {
        $movement = $this->movements->authorMovement($command->id, $command->authorId);
        $movement->submit();

        $this->movements->save($movement);
    }
}
