<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\GetCurrentUser;
use SocialBulletin\Core\User;
use SocialBulletin\Core\UserRepository;

class GetCurrentUserSpec extends ObjectBehavior
{
    public function let(UserRepository $users): void
    {
        $this->beConstructedWith($users);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(GetCurrentUser::class);
    }

    public function it_returns_current_user_when_found(UserRepository $users): void
    {
        $user = new User('018ff6f2-3a17-7b7e-94e3-0b3cdb6d1000', 'person@example.com');
        $users->findById($user->id)->willReturn($user);

        $this($user->id)->shouldReturn($user);
    }

    public function it_returns_null_when_user_is_missing(UserRepository $users): void
    {
        $users->findById('018ff6f2-3a17-7b7e-94e3-0b3cdb6d1000')->willReturn(null);

        $this('018ff6f2-3a17-7b7e-94e3-0b3cdb6d1000')->shouldReturn(null);
    }
}
