<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\EventListener\SessionCookieListener;
use App\Http\Attribute\JsonApi;
use App\Http\Attribute\JsonApiPayload;
use App\Http\Transformer\UserTransformer;
use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use App\Security\ApiUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use SocialBulletin\Core\Application\User\Command\SignInCommand;
use SocialBulletin\Core\Application\User\Model\User;
use SocialBulletin\Core\Application\User\Query\CurrentUserQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;

final readonly class SignInController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private JWTTokenManagerInterface $tokenManager,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[JsonApi(UserTransformer::class, 'users')]
    #[Route('/api/session', name: 'api_session_create', methods: ['POST'])]
    public function __invoke(Request $request, #[JsonApiPayload('sessions', SignInCommand::class)] array $payload): User
    {
        $command = SignInCommand::fromPayload($payload);

        $this->commandBus->dispatch($command);
        $user = $this->queryBus->dispatch(new CurrentUserQuery($command->email));

        Assert::notNull($user, 'Signing in left no user to read back.');

        $request->attributes->set(
            SessionCookieListener::TOKEN_ATTRIBUTE,
            $this->tokenManager->create(new ApiUser($user->email)),
        );

        return $user;
    }
}
