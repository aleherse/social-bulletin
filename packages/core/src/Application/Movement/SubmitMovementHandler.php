<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementService;

final readonly class SubmitMovementHandler
{
    public function __construct(
        private MovementService $movementService,
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
        $movement = $this->movementService->authorMovement($command->id, $command->authorId);
        $movement->submit();

        $this->movements->save($movement);
    }
}
