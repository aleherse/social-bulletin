<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\ApiUser;
use SocialBulletin\Core\Movement\InvalidMovement;
use SocialBulletin\Core\Movement\Movement;
use SocialBulletin\Core\Movement\MovementNotDraft;
use SocialBulletin\Core\Movement\MovementNotFound;
use SocialBulletin\Core\Movement\MovementService;
use SocialBulletin\Core\User\User;
use SocialBulletin\Core\User\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MovementController
{
    public function __construct(
        private readonly MovementService $movementService,
        private readonly UserService $userService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/api/movements', name: 'api_movements_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] ApiUser $apiUser): JsonResponse
    {
        $user = $this->author($apiUser);

        if (null === $user) {
            return $this->unauthorized();
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();

        try {
            $movement = $this->movementService->create(
                $user->id,
                self::stringField($payload, 'title'),
                self::stringField($payload, 'description'),
                self::stringField($payload, 'category'),
                self::stringField($payload, 'area'),
                self::nullableStringField($payload, 'location'),
            );
        } catch (InvalidMovement $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->movementJson($movement), Response::HTTP_CREATED);
    }

    #[Route('/api/movements', name: 'api_movements_list', methods: ['GET'])]
    public function list(#[CurrentUser] ApiUser $apiUser): JsonResponse
    {
        $user = $this->author($apiUser);

        if (null === $user) {
            return $this->unauthorized();
        }

        return new JsonResponse([
            'movements' => array_map(
                $this->movementJson(...),
                $this->movementService->byAuthor($user->id),
            ),
        ]);
    }

    #[Route('/api/movements/{id}', name: 'api_movements_show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] ApiUser $apiUser): JsonResponse
    {
        $user = $this->author($apiUser);

        if (null === $user) {
            return $this->unauthorized();
        }

        try {
            $movement = $this->movementService->authorMovement($id, $user->id);
        } catch (MovementNotFound $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->movementJson($movement));
    }

    #[Route('/api/movements/{id}', name: 'api_movements_update', methods: ['PATCH'])]
    public function update(string $id, Request $request, #[CurrentUser] ApiUser $apiUser): JsonResponse
    {
        $user = $this->author($apiUser);

        if (null === $user) {
            return $this->unauthorized();
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();

        try {
            // PATCH semantics: absent fields keep their current value.
            $movement = $this->movementService->authorMovement($id, $user->id);
            $movement = $this->movementService->update(
                $id,
                $user->id,
                \array_key_exists('title', $payload)
                    ? self::stringField($payload, 'title') : $movement->title(),
                \array_key_exists('description', $payload)
                    ? self::stringField($payload, 'description') : $movement->description(),
                \array_key_exists('category', $payload)
                    ? self::stringField($payload, 'category') : $movement->category(),
                \array_key_exists('area', $payload)
                    ? self::stringField($payload, 'area') : $movement->area()->value,
                \array_key_exists('location', $payload)
                    ? self::nullableStringField($payload, 'location') : $movement->location(),
            );
        } catch (MovementNotFound $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (MovementNotDraft $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (InvalidMovement $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->movementJson($movement));
    }

    #[Route('/api/movements/{id}/submit', name: 'api_movements_submit', methods: ['POST'])]
    public function submit(string $id, #[CurrentUser] ApiUser $apiUser): JsonResponse
    {
        $user = $this->author($apiUser);

        if (null === $user) {
            return $this->unauthorized();
        }

        try {
            $movement = $this->movementService->submit($id, $user->id);
        } catch (MovementNotFound $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (MovementNotDraft $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (InvalidMovement $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->movementJson($movement));
    }

    private function author(ApiUser $apiUser): ?User
    {
        return $this->userService->currentUser($apiUser->getUserIdentifier());
    }

    private function unauthorized(): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->translator->trans('error.unauthorized', [], 'errors'),
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array<string, string|null>
     */
    private function movementJson(Movement $movement): array
    {
        return [
            'id' => $movement->id,
            'title' => $movement->title(),
            'description' => $movement->description(),
            'category' => $movement->category(),
            'area' => $movement->area()
                ->value,
            'location' => $movement->location(),
            'status' => $movement->status()
                ->value,
            'createdAt' => $movement->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $movement->updatedAt()
                ->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function stringField(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        return \is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function nullableStringField(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return \is_string($value) ? $value : null;
    }
}
