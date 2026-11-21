<?php

declare(strict_types=1);

namespace App\Tests\Integration\Realtime;

use App\Realtime\TicketIssuer;
use App\Tests\Integration\IntegrationTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use SocialBulletin\Core\Application\User\Model\User;

final class TicketIssuerTest extends IntegrationTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User((string) $this->signIn('author@example.com'), 'author@example.com');
    }

    public function testTheTicketNamesTheUserForTheRealtimeAudienceOnly(): void
    {
        $ticket = self::service(TicketIssuer::class)->issue($this->user);

        $claims = self::service(JWTEncoderInterface::class)->decode($ticket->ticket);
        self::assertSame($this->user->id, $claims['sub']);
        self::assertSame(['realtime'], $claims['aud']);
        self::assertArrayNotHasKey('username', $claims, 'A ticket must not authenticate an API request.');
    }

    public function testTheTicketExpiresWithinAMinute(): void
    {
        $ticket = self::service(TicketIssuer::class)->issue($this->user);

        $claims = self::service(JWTEncoderInterface::class)->decode($ticket->ticket);
        self::assertSame(60, $claims['exp'] - $claims['iat']);
        self::assertSame($claims['exp'], $ticket->expiresAt->getTimestamp());
    }

    public function testItListsTheChannelsOfTheUser(): void
    {
        $ticket = self::service(TicketIssuer::class)->issue($this->user);

        self::assertSame(['/users/'.$this->user->id.'/movements'], $ticket->channels);
    }

    public function testEachTicketHasItsOwnId(): void
    {
        $issuer = self::service(TicketIssuer::class);

        self::assertNotSame($issuer->issue($this->user)->id, $issuer->issue($this->user)->id);
    }
}
