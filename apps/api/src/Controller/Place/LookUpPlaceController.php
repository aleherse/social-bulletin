<?php

declare(strict_types=1);

namespace App\Controller\Place;

use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Place\Query\LookUpPlaceQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ADR-0022: the point arrives in the body, never the URL, and is not kept (FR-005).
 */
final readonly class LookUpPlaceController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/places/lookup', name: 'api_places_lookup', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $place = $this->queryBus->dispatch(LookUpPlaceQuery::fromPayload($payload));

        return new JsonResponse([
            'name' => $place?->name,
        ]);
    }
}
