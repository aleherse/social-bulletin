<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use App\Tests\Support\DatabaseSnapshot;
use Doctrine\DBAL\Connection;
use SocialBulletin\Core\Application\User\Command\SignInCommand;
use SocialBulletin\Core\Application\User\Model\User;
use SocialBulletin\Core\Application\User\Query\CurrentUserQuery;
use SocialBulletin\Core\Domain\User\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        DatabaseSnapshot::restore(self::service(Connection::class));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    protected static function service(string $id): object
    {
        $service = self::getContainer()->get($id);
        self::assertInstanceOf($id, $service);

        return $service;
    }

    /**
     * test-integration-0001: state is arranged through the buses.
     */
    protected function signIn(string $email): UserId
    {
        self::service(CommandBus::class)->dispatch(SignInCommand::fromPayload([
            'email' => $email,
        ]));
        $user = self::service(QueryBus::class)->dispatch(new CurrentUserQuery($email));
        self::assertInstanceOf(User::class, $user);

        return UserId::from($user->id);
    }
}
