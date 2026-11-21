<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Movement\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\Helper\NotificationPublisher;
use SocialBulletin\Core\Application\Movement\Event\MovementChange;
use SocialBulletin\Core\Application\Movement\Event\MovementChanged;
use SocialBulletin\Core\Domain\Movement\Events\MovementHasBeenUpdated;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\User\UserId;

final class MovementUpdatedHandlerSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';

    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(NotificationPublisher $publisher): void
    {
        $this->beConstructedWith($publisher);
    }

    public function it_tells_the_author_a_movement_was_updated(NotificationPublisher $publisher): void
    {
        $publisher->publish(Argument::that(
            static fn (MovementChanged $notification): bool => MovementChange::Updated === $notification->change
                && self::ID === $notification->movementId
                && '/users/'.self::AUTHOR_ID.'/movements' === $notification->channel()
                && [
                    'change' => 'updated',
                    'movementId' => self::ID,
                ] === $notification->payload(),
        ))->shouldBeCalledOnce();

        $this->__invoke(new MovementHasBeenUpdated(MovementId::from(self::ID), UserId::from(self::AUTHOR_ID)));
    }
}
