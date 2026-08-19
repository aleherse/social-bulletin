<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Domain\User;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;

final class UserSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(): void
    {
        $this->beConstructedThrough('register', [self::ID, 'new.user@example.com']);
    }

    public function it_registers_a_user_for_an_email(): void
    {
        $this->id->shouldBe(self::ID);
        $this->email->shouldBe('new.user@example.com');
    }

    public function it_stores_the_email_without_surrounding_whitespace(): void
    {
        $this->beConstructedThrough('register', [self::ID, '  new.user@example.com  ']);

        $this->email->shouldBe('new.user@example.com');
    }

    public function it_rejects_a_blank_email(): void
    {
        $this->beConstructedThrough('register', [self::ID, '   ']);

        $this->shouldThrow(new InvalidEmailAddress('email.blank'))->duringInstantiation();
    }

    public function it_rejects_a_malformed_email(): void
    {
        $this->beConstructedThrough('register', [self::ID, 'not-an-email']);

        $this->shouldThrow(new InvalidEmailAddress('email.invalid'))->duringInstantiation();
    }
}
