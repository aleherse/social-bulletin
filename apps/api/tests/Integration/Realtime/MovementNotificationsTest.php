<?php

declare(strict_types=1);

namespace App\Tests\Integration\Realtime;

use App\Messenger\CommandBus;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Support\RecordingHttpClient;
use SocialBulletin\Core\Application\Movement\Command\CreateMovementCommand;
use SocialBulletin\Core\Application\Movement\Command\SubmitMovementCommand;
use SocialBulletin\Core\Application\Movement\Command\UpdateMovementCommand;
use SocialBulletin\Core\Domain\User\UserId;

final class MovementNotificationsTest extends IntegrationTestCase
{
    private UserId $authorId;

    private RecordingHttpClient $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorId = $this->signIn('author@example.com');
        $this->http = self::service(RecordingHttpClient::class);
    }

    public function testCreatingAMovementTellsItsAuthorsChannel(): void
    {
        $command = CreateMovementCommand::fromPayload($this->payload(), (string) $this->authorId);

        self::service(CommandBus::class)->dispatch($command);

        $this->assertPublished('created', (string) $command->id);
    }

    public function testUpdatingAMovementTellsItsAuthorsChannel(): void
    {
        $command = CreateMovementCommand::fromPayload($this->payload(), (string) $this->authorId);
        self::service(CommandBus::class)->dispatch($command);

        self::service(CommandBus::class)->dispatch(
            UpdateMovementCommand::fromPayload([
                'title' => 'Save All the Bees',
            ], (string) $this->authorId, (string) $command->id),
        );

        $this->assertPublished('updated', (string) $command->id, at: 1);
    }

    public function testSubmittingAMovementTellsItsAuthorsChannel(): void
    {
        $command = CreateMovementCommand::fromPayload($this->payload(), (string) $this->authorId);
        self::service(CommandBus::class)->dispatch($command);

        self::service(CommandBus::class)->dispatch(
            new SubmitMovementCommand((string) $command->id, (string) $this->authorId),
        );

        $this->assertPublished('submitted', (string) $command->id, at: 1);
    }

    public function testThePublishRequestIsSignedForAppSync(): void
    {
        self::service(CommandBus::class)->dispatch(
            CreateMovementCommand::fromPayload($this->payload(), (string) $this->authorId),
        );

        $headers = implode("\n", $this->http->requests()[0]['headers']);
        self::assertStringContainsString('Authorization: AWS4-HMAC-SHA256 Credential=', $headers);
        self::assertStringContainsString('/appsync/aws4_request', $headers);
        self::assertStringContainsString('X-Amz-Date: ', $headers);
    }

    private function assertPublished(string $change, string $movementId, int $at = 0): void
    {
        $request = $this->http->requests()[$at] ?? self::fail('Nothing was published.');

        self::assertSame('POST', $request['method']);
        self::assertSame('http://realtime.test/event', $request['url']);

        $body = json_decode($request['body'], true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('/users/'.$this->authorId.'/movements', $body['channel']);
        self::assertCount(1, $body['events']);
        self::assertSame(
            [
                'change' => $change,
                'movementId' => $movementId,
            ],
            json_decode($body['events'][0], true, flags: \JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'title' => 'Community Gardens for Everyone',
            'description' => "## Why\nGardens for all.",
            'category' => 'cooperative',
            'area' => 'municipality',
            'location' => 'Sheffield',
        ];
    }
}
