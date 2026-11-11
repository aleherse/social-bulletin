<?php

declare(strict_types=1);

namespace App\Controller\Place;

/**
 * The fields of a place lookup request, for the OpenAPI document; `LookUpPlaceQuery` validates them.
 */
final readonly class LookUpPlacePayload
{
    public function __construct(
        public float $lat,
        public float $lon,
        public string $area,
    ) {
    }
}
