<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

interface MovementRepository
{
    /**
     * Inserts the movement or updates it when the id already exists.
     */
    public function save(Movement $movement): void;

    public function byId(string $id): ?Movement;

    /**
     * @return list<Movement> newest first
     */
    public function byAuthor(string $authorId): array;
}
