<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Domain\User;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\User\User;
use SocialBulletin\Core\Domain\User\UserId;
use SocialBulletin\Core\Domain\User\UserRepository;

final class UserServiceSpec extends ObjectBehavior
{
    private const UUID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(UserRepository $users): void
    {
        $this->beConstructedWith($users);
    }

    public function it_finds_the_current_user_by_email(UserRepository $users): void
    {
        $user = User::restore(UserId::from(self::UUID), 'existing.user@example.com', new \DateTimeImmutable());
        $users->findByEmail('existing.user@example.com')->willReturn($user);

        $this->currentUser('existing.user@example.com')->shouldBe($user);
    }

    public function it_reports_no_current_user_for_an_unknown_email(UserRepository $users): void
    {
        $users->findByEmail('stranger@example.com')->willReturn(null);

        $this->currentUser('stranger@example.com')->shouldBe(null);
    }
}
