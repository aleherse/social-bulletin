<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\LogoutIntent;
use SocialBulletin\Core\LogoutUser;

class LogoutUserSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(LogoutUser::class);
    }

    public function it_returns_cookie_logout_intent(): void
    {
        $this()->shouldBeLike(new LogoutIntent('token'));
    }
}
