<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementService;

final readonly class UpdateMovementHandler
{
    public function __construct(
        private MovementService $movementService,
        private MovementRepository $movements,
    ) {
    }

    /**
     * @throws MovementNotFound when editing an unknown movement, or one owned by another author
     * @throws MovementNotDraft when editing a movement that already left `draft`
     * @throws InvalidMovement  when any field fails stage validation
     */
    public function __invoke(UpdateMovementCommand $command): void
    {
        $movement = $this->movementService->authorMovement($command->id, $command->authorId);
        $movement->edit(
            $command->hasProperty('title') ? $command->title : $movement->title(),
            $command->hasProperty('description') ? $command->description : $movement->description(),
            $command->hasProperty('category') ? $command->category : $movement->category(),
            $command->hasProperty('area') ? $command->area : $movement->area()
                ->value,
            $command->hasProperty('location') ? $command->location : $movement->location(),
        );

        $this->movements->save($movement);
    }
}
