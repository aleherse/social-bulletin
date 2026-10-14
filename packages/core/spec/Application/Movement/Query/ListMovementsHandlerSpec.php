<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement\Query;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Application\Movement\Model\Movement;
use SocialBulletin\Core\Application\Movement\Provider\MovementProvider;
use SocialBulletin\Core\Application\Movement\Query\ListMovementsQuery;
use SocialBulletin\Core\Domain\Movement\Area;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\Movement\MovementStatus;
use SocialBulletin\Core\Domain\User\UserId;

final class ListMovementsHandlerSpec extends ObjectBehavior
{
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(MovementProvider $movements): void
    {
        $this->beConstructedWith($movements);
    }

    public function it_lists_the_movements_the_provider_holds_for_the_author(
        MovementProvider $movements,
    ): void {
        $authorId = UserId::from(self::AUTHOR_ID);
        $movement = self::movement();
        $movements->byAuthor($authorId)->willReturn([$movement]);

        $this->__invoke(new ListMovementsQuery($authorId))->shouldReturn([$movement]);
    }

    public function it_lists_nothing_for_an_author_without_movements(
        MovementProvider $movements,
    ): void {
        $authorId = UserId::from(self::AUTHOR_ID);
        $movements->byAuthor($authorId)->willReturn([]);

        $this->__invoke(new ListMovementsQuery($authorId))->shouldReturn([]);
    }

    private static function movement(): Movement
    {
        return new Movement(
            MovementId::from('0198f2f0-6d2c-7cf0-a2b8-222222222222'),
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
