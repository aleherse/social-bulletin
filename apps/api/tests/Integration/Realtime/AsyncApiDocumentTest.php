<?php

declare(strict_types=1);

namespace App\Tests\Integration\Realtime;

use App\Realtime\AsyncApi\AsyncApiDocument;
use App\Tests\Integration\IntegrationTestCase;
use SocialBulletin\Core\Application\Helper\Notification;
use SocialBulletin\Core\Application\Movement\Event\MovementChange;
use SocialBulletin\Core\Application\Movement\Event\MovementChanged;

final class AsyncApiDocumentTest extends IntegrationTestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->document = self::service(AsyncApiDocument::class)->build();
    }

    public function testItIsAnAsyncApiThreeDocument(): void
    {
        self::assertSame('3.1.0', $this->document['asyncapi']);
    }

    public function testTheUserChannelIsAddressedLikeTheChannelTheApiPublishesTo(): void
    {
        $channel = $this->document['channels']['userMovements'];

        self::assertSame(MovementChanged::channelFor('{userId}'), $channel['address']);
        self::assertArrayHasKey('userId', $channel['parameters']);
    }

    public function testTheClientOnlyReceivesMovementChanges(): void
    {
        $operation = $this->document['operations']['receiveMovementChanged'];

        self::assertSame('receive', $operation['action']);
        self::assertSame('#/channels/userMovements', $operation['channel']['$ref']);
    }

    public function testThePayloadSchemaMatchesWhatTheApiPublishes(): void
    {
        $schema = $this->document['components']['schemas']['MovementChanged'];

        foreach (MovementChange::cases() as $change) {
            $payload = (new MovementChanged($change, 'movement-id', 'author-id'))->payload();

            self::assertEqualsCanonicalizing(array_keys($payload), array_keys($schema['properties']));
            self::assertEqualsCanonicalizing(array_keys($payload), $schema['required']);
            self::assertContains($payload['change'], $schema['properties']['change']['enum']);
        }
        self::assertEqualsCanonicalizing(
            array_map(static fn (MovementChange $change): string => $change->value, MovementChange::cases()),
            $schema['properties']['change']['enum'],
        );
        self::assertFalse($schema['additionalProperties']);
    }

    public function testEveryNotificationTheCoreCanPublishIsDescribed(): void
    {
        $described = array_keys($this->document['components']['schemas']);

        $notifications = [];
        foreach (glob(\dirname(__DIR__, 5).'/packages/core/src/Application/*/Event/*.php') ?: [] as $file) {
            $class = 'SocialBulletin\\Core\\Application\\'.str_replace('/', '\\', substr($file, \strlen(\dirname(__DIR__, 5).'/packages/core/src/Application/'), -4));

            if (is_subclass_of($class, Notification::class)) {
                $notifications[] = (new \ReflectionClass($class))->getShortName();
            }
        }

        self::assertNotEmpty($notifications);
        self::assertEqualsCanonicalizing($notifications, $described, 'List each notification in config/asyncapi.yaml.');
    }

    public function testSubscribingRequiresTheTicket(): void
    {
        $scheme = $this->document['components']['securitySchemes']['ticket'];

        self::assertSame('http', $scheme['type']);
        self::assertSame('bearer', $scheme['scheme']);
        self::assertSame('JWT', $scheme['bearerFormat']);
        self::assertStringContainsString('/api/realtime-tickets', $scheme['description']);
    }
}
