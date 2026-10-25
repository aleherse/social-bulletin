<?php

declare(strict_types=1);

namespace App\Http\Attribute;

use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks a controller whose return value is rendered as a JSON:API document by `JsonApiViewListener`.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class JsonApi
{
    /**
     * @param class-string<TransformerAbstract> $transformer maps one returned model to the resource's attributes
     * @param string                            $type        the JSON:API resource type, e.g. `movements`
     */
    public function __construct(
        public string $transformer,
        public string $type,
        public int $status = Response::HTTP_OK,
    ) {
    }
}
