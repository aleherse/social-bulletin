<?php

declare(strict_types=1);

namespace App\Controller\Place;

use App\Http\Attribute\JsonApi;
use App\Http\Attribute\JsonApiPayload;
use App\Http\Transformer\PlaceTransformer;
use App\Messenger\QueryBus;
use SocialBulletin\Core\Application\Place\Model\Place;
use SocialBulletin\Core\Application\Place\Query\LookUpPlaceQuery;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ADR-0024: the point arrives in the body, never the URL, and is not kept (FR-005).
 */
final readonly class LookUpPlaceController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[JsonApi(PlaceTransformer::class, 'places')]
    #[Route('/api/places/lookup', name: 'api_places_lookup', methods: ['POST'])]
    public function __invoke(#[JsonApiPayload('places', LookUpPlacePayload::class)] array $payload): Place
    {
        return $this->queryBus->dispatch(LookUpPlaceQuery::fromPayload($payload));
    }
}
