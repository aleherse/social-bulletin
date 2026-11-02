<?php

declare(strict_types=1);

namespace App\Realtime;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use SocialBulletin\Core\Application\Movement\Event\MovementChanged;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

/**
 * Signs the short-lived ticket a browser presents to the AppSync Lambda authorizer (ADR-0024).
 *
 * It carries no `username` claim, so the API's own authenticator never accepts it as a session.
 */
final readonly class TicketIssuer
{
    private const int LIFETIME_SECONDS = 60;

    private const string AUDIENCE = 'realtime';

    public function __construct(
        private JWTEncoderInterface $encoder,
        #[Autowire('%env(REALTIME_WS_URL)%')]
        private string $endpoint,
    ) {
    }

    public function issue(User $user): RealtimeTicket
    {
        $issuedAt = new \DateTimeImmutable();
        $expiresAt = $issuedAt->modify(sprintf('+%d seconds', self::LIFETIME_SECONDS));
        $id = Uuid::v7()->toRfc4122();

        return new RealtimeTicket(
            $id,
            $this->encoder->encode([
                'sub' => $user->id,
                'aud' => self::AUDIENCE,
                'jti' => $id,
                'iat' => $issuedAt->getTimestamp(),
                'exp' => $expiresAt->getTimestamp(),
            ]),
            $expiresAt,
            $this->endpoint,
            [MovementChanged::channelFor($user->id)],
        );
    }
}
