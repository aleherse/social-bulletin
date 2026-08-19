<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Domain\Helper\IdentityGenerator;
use SocialBulletin\Core\Domain\Movement\InvalidMovement;
use SocialBulletin\Core\Domain\Movement\Movement;
use SocialBulletin\Core\Domain\Movement\MovementRepository;
use Webmozart\Assert\Assert;

final readonly class CreateMovementHandler
{
    public function __construct(
        private MovementRepository $movements,
        private IdentityGenerator $identities,
    ) {
    }

    /**
     * @throws InvalidMovement when any field fails stage validation
     */
    public function __invoke(CreateMovementCommand $command): Movement
    {
        $id = $this->identities->generate();
        Assert::uuid($id);

        return $this->movements->save(Movement::draft(
            $id,
            $command->authorId,
            $command->title,
            $command->description,
            $command->category,
            $command->area,
            $command->location,
        ));
    }
}
