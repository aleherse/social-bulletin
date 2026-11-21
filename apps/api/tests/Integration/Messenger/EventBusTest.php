<?php

declare(strict_types=1);

namespace App\Tests\Integration\Messenger;

use App\Messenger\EventBus;
use App\Tests\Integration\IntegrationTestCase;
use SocialBulletin\Core\Domain\Movement\Events\MovementHasBeenCreated;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\User\UserId;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class EventBusTest extends IntegrationTestCase
{
    public function testAnEventNobodyListensToIsAccepted(): void
    {
        self::service(EventBus::class)->dispatch($this->event());

        $this->addToAssertionCount(1);
    }

    public function testAListenerFailureSurfacesUnwrapped(): void
    {
        $bus = new EventBus(new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                MovementHasBeenCreated::class => [
                    static function (): never {
                        throw new \LogicException('listener failed');
                    },
                ],
            ])),
        ]));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('listener failed');

        $bus->dispatch($this->event());
    }

    private function event(): MovementHasBeenCreated
    {
        return new MovementHasBeenCreated(MovementId::generate(), UserId::generate());
    }
}
