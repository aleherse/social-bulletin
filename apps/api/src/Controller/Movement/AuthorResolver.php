<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Security\ApiUser;
use SocialBulletin\Core\User\User;
use SocialBulletin\Core\User\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AuthorResolver
{
    public function __construct(
        private UserService $userService,
        private TranslatorInterface $translator,
    ) {
    }

    public function resolve(ApiUser $apiUser): User|JsonResponse
    {
        $user = $this->userService->currentUser($apiUser->getUserIdentifier());

        if (null === $user) {
            return new JsonResponse([
                'message' => $this->translator->trans('error.unauthorized', [], 'errors'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }
}
