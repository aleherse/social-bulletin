<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Domain\Helper\IdentityGenerator;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementNotDraft;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use SocialBulletin\Core\Domain\Movement\MovementService;
use Webmozart\Assert\Assert;

final readonly class SaveMovementHandler
{
    public function __construct(
        private MovementService $movementService,
        private MovementRepository $movements,
        private IdentityGenerator $identities,
    ) {
    }

    /**
     * @throws MovementNotFound when editing an unknown movement, or one owned by another author
     * @throws MovementNotDraft when editing a movement that already left `draft`
     * @throws InvalidMovement  when any field fails stage validation
     */
    public function __invoke(SaveMovementCommand $command): Movement
    {
        if (null === $command->id) {
            return $this->create($command);
        }

        return $this->edit($command->id, $command);
    }

    /**
     * An absent field reaches the domain empty, which rejects it with a field error.
     */
    private function create(SaveMovementCommand $command): Movement
    {
        $id = $this->identities->generate();
        Assert::uuid($id);

        return $this->movements->save(Movement::draft(
            $id,
            $command->authorId,
            $command->hasProperty('title') ? $command->title : '',
            $command->hasProperty('description') ? $command->description : '',
            $command->hasProperty('category') ? $command->category : '',
            $command->hasProperty('area') ? $command->area : '',
            $command->hasProperty('location') ? $command->location : null,
        ));
    }

    /**
     * An absent field keeps the value the movement already holds.
     */
    private function edit(string $id, SaveMovementCommand $command): Movement
    {
        $movement = $this->movementService->authorMovement($id, $command->authorId);
        $movement->edit(
            $command->hasProperty('title') ? $command->title : $movement->title(),
            $command->hasProperty('description') ? $command->description : $movement->description(),
            $command->hasProperty('category') ? $command->category : $movement->category(),
            $command->hasProperty('area') ? $command->area : $movement->area()
                ->value,
            $command->hasProperty('location') ? $command->location : $movement->location(),
        );

        return $this->movements->save($movement);
    }
}
