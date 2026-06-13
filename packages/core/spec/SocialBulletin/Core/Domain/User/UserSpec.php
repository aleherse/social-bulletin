<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Domain\User;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\User\User;
use Symfony\Component\Uid\Uuid;

class UserSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beAnInstanceOf(User::class);
        $this->beConstructedWith(Uuid::v7(), 'test@example.com', new \DateTimeImmutable());
    }

    public function it_exposes_the_email_address(): void
    {
        $this->email()->shouldBe('test@example.com');
    }
}
