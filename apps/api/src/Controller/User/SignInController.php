<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Messenger\CommandBus;
use App\Messenger\QueryBus;
use App\Security\ApiUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use SocialBulletin\Core\Application\User\Command\SignInCommand;
use SocialBulletin\Core\Application\User\Query\CurrentUserQuery;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/api/session', name: 'api_session_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $command = SignInCommand::fromPayload($payload);

        try {
            $this->commandBus->dispatch($command);
            $user = $this->queryBus->dispatch(new CurrentUserQuery($command->email));
        } catch (InvalidEmailAddress $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Assert::notNull($user, 'Signing in left no user to read back.');

        $jwt = $this->tokenManager->create(new ApiUser($user->email));

        $response = new JsonResponse([
            'email' => $user->email,
        ]);
        $response->headers->setCookie(SessionCookie::create($jwt));

        return $response;
    }
}
