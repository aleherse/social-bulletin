<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\User;

use PhpSpec\ObjectBehavior;

class EmailAddressSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedWith(' User@Example.COM ');
    }

    public function it_normalises_email_addresses(): void
    {
        $this->value()->shouldReturn('user@example.com');
    }

    public function it_rejects_invalid_email_addresses(): void
    {
        $this->beConstructedWith('not-an-email');

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
