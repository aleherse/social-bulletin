<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\User;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Helper\IdentityGenerator;
use SocialBulletin\Core\User\InvalidEmailAddress;
use SocialBulletin\Core\User\User;
use SocialBulletin\Core\User\UserRepository;

final class UserServiceSpec extends ObjectBehavior
{
    private const UUID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(
        UserRepository $users,
        IdentityGenerator $identities,
    ): void {
        $this->beConstructedWith($users, $identities);
    }

    public function it_creates_a_user_for_an_unknown_email(
        UserRepository $users,
        IdentityGenerator $identities,
    ): void {
        $users->findByEmail('new.user@example.com')->willReturn(null);
        $identities->generate()->willReturn(self::UUID);
        $users->add(\Prophecy\Argument::that(
            static fn (User $user): bool => self::UUID === $user->id && 'new.user@example.com' === $user->email,
        ))->shouldBeCalled();

        $user = $this->findOrCreateByEmail('new.user@example.com');
        $user->email->shouldBe('new.user@example.com');
        $user->id->shouldBe(self::UUID);
    }

    public function it_reuses_the_existing_user_for_a_known_email(
        UserRepository $users,
        IdentityGenerator $identities,
    ): void {
        $existing = new User(self::UUID, 'existing.user@example.com', new \DateTimeImmutable());
        $users->findByEmail('existing.user@example.com')->willReturn($existing);
        $identities->generate()->shouldNotBeCalled();
        $users->add(\Prophecy\Argument::any())->shouldNotBeCalled();

        $this->findOrCreateByEmail('existing.user@example.com')->shouldBe($existing);
    }

    public function it_rejects_a_malformed_email(
        UserRepository $users,
    ): void {
        $users->add(\Prophecy\Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(
            new InvalidEmailAddress('email.invalid'),
        )->during('findOrCreateByEmail', ['not-an-email']);
    }

    public function it_rejects_a_blank_email(): void
    {
        $this->shouldThrow(
            new InvalidEmailAddress('email.blank'),
        )->during('findOrCreateByEmail', ['   ']);
    }

    public function it_finds_the_current_user_by_email(UserRepository $users): void
    {
        $user = new User(self::UUID, 'existing.user@example.com', new \DateTimeImmutable());
        $users->findByEmail('existing.user@example.com')->willReturn($user);

        $this->currentUser('existing.user@example.com')->shouldBe($user);
    }
}
