<?php

namespace spec\SocialBulletin\Core;

use SocialBulletin\Core\User;
use PhpSpec\ObjectBehavior;

class UserSpec extends ObjectBehavior
{
    function it_creates_a_user_with_id_and_email(): void
    {
        $id = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $this->beConstructedWith($id, 'test@example.com');

        $this->id()->shouldReturn($id);
        $this->email()->shouldReturn('test@example.com');
    }

    function it_normalises_email_to_lowercase(): void
    {
        $this->beConstructedWith('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'TEST@EXAMPLE.COM');

        $this->email()->shouldReturn('test@example.com');
    }

    function it_rejects_empty_email(): void
    {
        $this->beConstructedWith('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', '');

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
