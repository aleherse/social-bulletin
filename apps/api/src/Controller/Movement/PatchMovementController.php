<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use App\Controller\RequestPayload;
use SocialBulletin\Core\Movement\InvalidMovement;
use SocialBulletin\Core\Movement\MovementNotDraft;
use SocialBulletin\Core\Movement\MovementNotFound;
use SocialBulletin\Core\Movement\MovementService;
use SocialBulletin\Core\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PatchMovementController
{
    public function __construct(
        private MovementService $movementService,
    ) {
    }

    #[Route('/api/movements/{id}', name: 'api_movements_update', methods: ['PATCH'])]
    public function __invoke(string $id, Request $request, User $author): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();

        try {
            // PATCH semantics: absent fields keep their current value.
            $movement = $this->movementService->authorMovement($id, $author->id);
            $movement = $this->movementService->update(
                $id,
                $author->id,
                \array_key_exists('title', $payload)
                    ? RequestPayload::stringField($payload, 'title') : $movement->title(),
                \array_key_exists('description', $payload)
                    ? RequestPayload::stringField($payload, 'description') : $movement->description(),
                \array_key_exists('category', $payload)
                    ? RequestPayload::stringField($payload, 'category') : $movement->category(),
                \array_key_exists('area', $payload)
                    ? RequestPayload::stringField($payload, 'area') : $movement->area()->value,
                \array_key_exists('location', $payload)
                    ? RequestPayload::nullableStringField($payload, 'location') : $movement->location(),
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

        return new JsonResponse(MovementPresenter::toArray($movement));
    }
}
