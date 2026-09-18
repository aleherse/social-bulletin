<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement\Query;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Provider\MovementProvider;
use SocialBulletin\Core\Application\Movement\Query\ShowMovementQuery;
use SocialBulletin\Core\Domain\Movement\Area;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\Movement\MovementNotFound;
use SocialBulletin\Core\Domain\Movement\MovementStatus;
use SocialBulletin\Core\Domain\User\UserId;

final class ShowMovementHandlerSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(MovementProvider $movements): void
    {
        $this->beConstructedWith($movements);
    }

    public function it_shows_the_movement_the_provider_scoped_to_the_author(
        MovementProvider $movements,
    ): void {
        $authorId = UserId::from(self::AUTHOR_ID);
        $movement = self::movement();
        $movements->authorMovement(self::ID, $authorId)->willReturn($movement);

        $this->__invoke(new ShowMovementQuery(self::ID, $authorId))->shouldReturn($movement);
    }

    public function it_hides_a_movement_the_provider_does_not_return(
        MovementProvider $movements,
    ): void {
        $authorId = UserId::from(self::AUTHOR_ID);
        $movements->authorMovement(self::ID, $authorId)
            ->willThrow(new MovementNotFound('movement.not_found'));

        $this->shouldThrow(MovementNotFound::class)->during('__invoke', [
            new ShowMovementQuery(self::ID, $authorId),
        ]);
    }

    private static function movement(): Movement
    {
        return new Movement(
            MovementId::from(self::ID),
            'Community Gardens for Everyone',
            "## Why\nGardens for all.",
            'cooperative',
            Area::Municipality,
            'Sheffield',
            MovementStatus::Draft,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
        );
    }
}
