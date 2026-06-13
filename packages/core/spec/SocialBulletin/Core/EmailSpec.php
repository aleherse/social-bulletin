<?php

namespace spec\SocialBulletin\Core;

use SocialBulletin\Core\Email;
use PhpSpec\ObjectBehavior;

class EmailSpec extends ObjectBehavior
{
    function it_accepts_a_valid_email(): void
    {
        $this->beConstructedWith('user@example.com');

        $this->value()->shouldReturn('user@example.com');
    }

    function it_normalises_email_to_lowercase(): void
    {
        $this->beConstructedWith('USER@EXAMPLE.COM');

        $this->value()->shouldReturn('user@example.com');
    }

    function it_trims_whitespace(): void
    {
        $this->beConstructedWith('  user@example.com  ');

        $this->value()->shouldReturn('user@example.com');
    }

    function it_rejects_an_invalid_email(): void
    {
        $this->beConstructedWith('not-an-email');

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
