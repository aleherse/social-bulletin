<?php

declare(strict_types=1);

namespace App\Http\Transformer;

use League\Fractal\TransformerAbstract;
use SocialBulletin\Core\Application\Place\Model\Place;

final class PlaceTransformer extends TransformerAbstract
{
    /**
     * A lookup is not stored, so its single answer carries a fixed id, never the point asked about (FR-005).
     *
     * @return array{id: string, name: string|null}
     */
    public function transform(Place $place): array
    {
        return [
            'id' => 'lookup',
            'name' => $place->name,
        ];
    }
}
