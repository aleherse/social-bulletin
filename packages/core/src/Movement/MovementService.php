<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

use SocialBulletin\Core\Helper\IdentityGenerator;
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
    public function create(
        string $authorId,
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
    ): Movement {
        $id = $this->identities->generate();
        Assert::uuid($id);

        $movement = Movement::draft(
            $id,
            $authorId,
            $title,
            $description,
            $category,
            $area,
            $location,
            new \DateTimeImmutable(),
        );
        $this->movements->save($movement);

        return $movement;
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
    public function update(
        string $id,
        string $authorId,
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
    ): Movement {
        $movement = $this->authorMovement($id, $authorId);
        $movement->edit(
            $title,
            $description,
            $category,
            $area,
            $location,
            new \DateTimeImmutable(),
        );
        $this->movements->save($movement);

        return $movement;
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when the description is still empty
     */
    public function submit(string $id, string $authorId): Movement
    {
        $movement = $this->authorMovement($id, $authorId);
        $movement->submit(new \DateTimeImmutable());
        $this->movements->save($movement);

        return $movement;
    }
}
