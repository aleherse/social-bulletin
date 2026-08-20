<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\Movement\MovementRepository;

final readonly class CreateMovementHandler
{
    public function __construct(
        private MovementRepository $movements,
    ) {
    }

    /**
     * @throws InvalidMovement when any field fails stage validation
     */
    public function __invoke(CreateMovementCommand $command): Movement
    {
        return $this->movements->save(Movement::draft(
            MovementId::generate(),
            $command->authorId,
            $command->title,
            $command->description,
            $command->category,
            $command->area,
            $command->location,
        ));
    }
}
