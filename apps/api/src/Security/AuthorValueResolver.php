<?php

declare(strict_types=1);

namespace App\Security;

use SocialBulletin\Core\Domain\User\User;
use SocialBulletin\Core\Domain\User\UserService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Resolves a `User $author` controller argument to the domain user behind the
 * authenticated request, so controllers no longer resolve it by hand.
 */
final readonly class AuthorValueResolver implements ValueResolverInterface
{
    public function __construct(
        private Security $security,
        private UserService $userService,
    ) {
    }

    /**
     * @return iterable<User>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (User::class !== $argument->getType()) {
            return [];
        }

        $apiUser = $this->security->getUser();

        if (! $apiUser instanceof ApiUser) {
            return [];
        }

        $author = $this->userService->currentUser($apiUser->getUserIdentifier());

        if (null === $author) {
            throw new AuthorNotFound();
        }

        return [$author];
    }
}
