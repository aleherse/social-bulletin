<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\User\Query;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;

final class CurrentUserQuerySpec extends ObjectBehavior
{
    public function it_keeps_an_email_that_is_already_normalised(): void
    {
        $this->beConstructedWith('author@example.com');

        $this->email->shouldBe('author@example.com');
    }

    public function it_trims_the_email_on_construction(): void
    {
        $this->beConstructedWith('  author@example.com  ');

        $this->email->shouldBe('author@example.com');
    }

    public function it_rejects_a_blank_email(): void
    {
        $this->beConstructedWith('   ');

        $this->shouldThrow(InvalidEmailAddress::class)->duringInstantiation();
    }

    public function it_rejects_an_email_that_is_not_one(): void
    {
        $this->beConstructedWith('not-an-email');

        $this->shouldThrow(InvalidEmailAddress::class)->duringInstantiation();
    }
}
