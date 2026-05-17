<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use SocialBulletin\Api\Application\Port\AuthTokenPort;
use SocialBulletin\Api\Application\Model\User;
use SocialBulletin\Api\Application\ValueObject\EmailAddress;

final class LexikJwtTokenIssuer implements AuthTokenPort
{
    public function __construct(private readonly JWTTokenManagerInterface $jwtManager)
    {
    }

    public function issueFor(string $userId): string
    {
        $stubUser = new SecurityUser(new User(
            id: $userId,
            email: new EmailAddress('stub@token.issuer'),
            passwordHash: '',
            termsAcceptedAt: new \DateTimeImmutable(),
            registeredAt: new \DateTimeImmutable(),
        ));

        return $this->jwtManager->createFromPayload($stubUser, ['sub' => $userId]);
    }
}
