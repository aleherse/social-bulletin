<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

use SocialBulletin\Core\Domain\Helper\IdentityGenerator;
use Webmozart\Assert\Assert;

final readonly class MovementService
{
    public function __construct(
        private MovementRepository $movements,
        private IdentityGenerator $identities,
    ) {
    }

    /**
     * @throws InvalidMovement when any field fails stage validation
     */
    public function create(DraftMovement $command): Movement
    {
        $id = $this->identities->generate();
        Assert::uuid($id);

        return $this->movements->save(Movement::draft($id, $command));
    }

    /**
     * @return list<Movement> newest first
     */
    public function byAuthor(string $authorId): array
    {
        Assert::uuid($authorId);

        return $this->movements->byAuthor($authorId);
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     */
    public function authorMovement(string $id, string $authorId): Movement
    {
        $movement = $this->movements->byId($id);

        if (null === $movement || $movement->authorId !== $authorId) {
            throw new MovementNotFound('movement.not_found');
        }

        return $movement;
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when any field fails stage validation
     */
    public function update(string $id, string $authorId, EditMovement $command): Movement
    {
        return $this->apply($id, $authorId, $command);
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when the description is still empty
     */
    public function submit(string $id, string $authorId): Movement
    {
        return $this->apply($id, $authorId, new SubmitMovement());
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when the command fails stage validation
     */
    private function apply(string $id, string $authorId, MovementCommand $command): Movement
    {
        $movement = $this->authorMovement($id, $authorId);
        $movement->apply($command);

        return $this->movements->save($movement);
    }
}
