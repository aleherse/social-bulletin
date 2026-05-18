<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core;

use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\IdentityGenerator;
use SocialBulletin\Core\InvalidEmailAddress;
use SocialBulletin\Core\User;
use SocialBulletin\Core\UserRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserServiceSpec extends ObjectBehavior
{
    private const UUID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(
        UserRepository $users,
        IdentityGenerator $identities,
        TranslatorInterface $translator,
    ): void {
        $this->beConstructedWith($users, $identities, $translator);
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

    public function it_rejects_a_malformed_email_with_a_translated_message(
        UserRepository $users,
        TranslatorInterface $translator,
    ): void {
        $translator->trans('email.invalid', ['email' => 'not-an-email'], 'validators')
            ->willReturn('The email address not-an-email is not valid.');
        $users->add(\Prophecy\Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(
            new InvalidEmailAddress('The email address not-an-email is not valid.'),
        )->during('findOrCreateByEmail', ['not-an-email']);
    }

    public function it_rejects_a_blank_email_with_a_translated_message(
        TranslatorInterface $translator,
    ): void {
        $translator->trans('email.blank', [], 'validators')
            ->willReturn('An email address is required.');

        $this->shouldThrow(
            new InvalidEmailAddress('An email address is required.'),
        )->during('findOrCreateByEmail', ['   ']);
    }

    public function it_finds_the_current_user_by_email(UserRepository $users): void
    {
        $user = new User(self::UUID, 'existing.user@example.com', new \DateTimeImmutable());
        $users->findByEmail('existing.user@example.com')->willReturn($user);

        $this->currentUser('existing.user@example.com')->shouldBe($user);
    }
}
