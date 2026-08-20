<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\User;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\User\SignInCommand;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;
use SocialBulletin\Core\Domain\User\User;
use SocialBulletin\Core\Domain\User\UserId;
use SocialBulletin\Core\Domain\User\UserRepository;
use Symfony\Component\Uid\Uuid;

final class SignInHandlerSpec extends ObjectBehavior
{
    private const UUID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(UserRepository $users): void
    {
        $this->beConstructedWith($users);
    }

    public function it_registers_a_user_for_an_unknown_email(
        UserRepository $users,
    ): void {
        $users->findByEmail('new.user@example.com')->willReturn(null);
        $users->save(Argument::that(
            static fn (User $user): bool => Uuid::isValid((string) $user->id) && 'new.user@example.com' === $user->email,
        ))->will(static function (array $arguments): User {
            // The repository returns the stored row as a fresh aggregate.
            $user = $arguments[0];
            \assert($user instanceof User);

            return $user;
        })->shouldBeCalled();

        $user = $this->__invoke(self::command('new.user@example.com'));
        $user->email->shouldBe('new.user@example.com');
    }

    public function it_reuses_the_existing_user_for_a_known_email(
        UserRepository $users,
    ): void {
        $existing = User::restore(UserId::from(self::UUID), 'existing.user@example.com', new \DateTimeImmutable());
        $users->findByEmail('existing.user@example.com')->willReturn($existing);
        $users->save(Argument::any())->shouldNotBeCalled();

        $this->__invoke(self::command('existing.user@example.com'))->shouldBe($existing);
    }

    public function it_looks_up_the_email_without_surrounding_whitespace(
        UserRepository $users,
    ): void {
        $existing = User::restore(UserId::from(self::UUID), 'existing.user@example.com', new \DateTimeImmutable());
        $users->findByEmail('existing.user@example.com')->willReturn($existing);

        $this->__invoke(self::command('  existing.user@example.com  '))->shouldBe($existing);
    }

    public function it_registers_nobody_for_a_malformed_email(UserRepository $users): void
    {
        $users->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(new InvalidEmailAddress('email.invalid'))
            ->during('__invoke', [self::command('not-an-email')]);
    }

    public function it_registers_nobody_for_a_blank_email(UserRepository $users): void
    {
        $users->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(new InvalidEmailAddress('email.blank'))
            ->during('__invoke', [self::command('   ')]);
    }

    public function it_registers_nobody_when_the_payload_carries_no_email(UserRepository $users): void
    {
        $users->save(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(new InvalidEmailAddress('email.blank'))
            ->during('__invoke', [SignInCommand::fromPayload([])]);
    }

    private static function command(string $email): SignInCommand
    {
        return SignInCommand::fromPayload(['email' => $email]);
    }
}
