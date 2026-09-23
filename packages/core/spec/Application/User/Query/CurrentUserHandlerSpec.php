<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\User\Query;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Application\User\Model\User;
use SocialBulletin\Core\Application\User\Provider\UserProvider;
use SocialBulletin\Core\Application\User\Query\CurrentUserQuery;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;
use SocialBulletin\Core\Domain\User\UserId;

final class CurrentUserHandlerSpec extends ObjectBehavior
{
    public function let(UserProvider $users): void
    {
        $this->beConstructedWith($users);
    }

    public function it_returns_the_user_behind_an_email(UserProvider $users): void
    {
        $user = new User(UserId::from('0198f2f0-6d2c-7cf0-a2b8-111111111111'), 'author@example.com');
        $users->currentUser('author@example.com')->willReturn($user);

        $this->__invoke(new CurrentUserQuery('author@example.com'))->shouldReturn($user);
    }

    public function it_returns_nothing_for_an_unknown_email(UserProvider $users): void
    {
        $users->currentUser('nobody@example.com')->willReturn(null);

        $this->__invoke(new CurrentUserQuery('nobody@example.com'))->shouldReturn(null);
    }

    public function it_looks_the_user_up_under_the_normalised_email(UserProvider $users): void
    {
        $user = new User(UserId::from('0198f2f0-6d2c-7cf0-a2b8-111111111111'), 'author@example.com');
        $users->currentUser('author@example.com')->willReturn($user);

        $this->__invoke(new CurrentUserQuery('  author@example.com  '))->shouldReturn($user);
    }

    public function it_rejects_an_email_that_is_not_one(): void
    {
        $this->shouldThrow(InvalidEmailAddress::class)
            ->during('__invoke', [new CurrentUserQuery('not-an-email')]);
    }
}
