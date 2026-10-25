<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Http\Attribute\JsonApi;
use App\Http\Transformer\UserTransformer;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\Routing\Attribute\Route;

final class CurrentUserController
{
    #[JsonApi(UserTransformer::class, 'users')]
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(User $currentUser): User
    {
        return $currentUser;
    }
}
