<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Messenger\CommandBus;
use App\Security\ApiUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use SocialBulletin\Core\Application\User\SignInCommand;
use SocialBulletin\Core\Domain\User\InvalidEmailAddress;
use SocialBulletin\Core\Domain\User\User;
use SocialBulletin\Core\Domain\User\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;

final readonly class SignInController
{
    public function __construct(
        private CommandBus $commandBus,
        private UserService $userService,
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
            $email = User::normaliseEmail($command->email);
            $this->commandBus->dispatch($command);
        } catch (InvalidEmailAddress $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->userService->currentUser($email);
        Assert::notNull($user, 'Signing in left no user to read back.');

        $jwt = $this->tokenManager->create(new ApiUser($user->email));

        $response = new JsonResponse([
            'email' => $user->email,
        ]);
        $response->headers->setCookie(SessionCookie::create($jwt));

        return $response;
    }
}
