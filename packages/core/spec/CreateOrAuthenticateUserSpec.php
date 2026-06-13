<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\CreateOrAuthenticateUser;
use SocialBulletin\Core\InvalidEmailAddress;
use SocialBulletin\Core\User;
use SocialBulletin\Core\UserRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateOrAuthenticateUserSpec extends ObjectBehavior
{
    public function let(UserRepository $users, TranslatorInterface $translator): void
    {
        $translator->trans('email_invalid', [], 'validators')->willReturn('Enter a valid email address.');
        $this->beConstructedWith($users, $translator);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(CreateOrAuthenticateUser::class);
    }

    public function it_returns_an_existing_user(UserRepository $users): void
    {
        $user = new User('018ff6f2-3a17-7b7e-94e3-0b3cdb6d1000', 'person@example.com');
        $users->findByEmail('person@example.com')->willReturn($user);

        $this(' PERSON@example.com ')->shouldReturn($user);
    }

    public function it_creates_a_missing_user(UserRepository $users): void
    {
        $users->findByEmail('new@example.com')->willReturn(null);
        $users->save(Argument::that(
            static fn (User $user): bool => $user->email === 'new@example.com',
        ))->shouldBeCalled();

        $this('new@example.com')->shouldBeAnInstanceOf(User::class);
    }

    public function it_rejects_invalid_email(): void
    {
        $this->shouldThrow(InvalidEmailAddress::class)->during('__invoke', ['not-an-email']);
    }
}
