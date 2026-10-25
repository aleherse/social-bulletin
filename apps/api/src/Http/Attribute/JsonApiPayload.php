<?php

declare(strict_types=1);

namespace App\Http\Attribute;

/**
 * Marks a controller's `array $payload` argument as the `attributes` of the JSON:API resource object in the request
 * body, resolved by `JsonApiPayloadResolver`.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class JsonApiPayload
{
    /**
     * @param string       $type    the resource type the request must declare, e.g. `movements`
     * @param class-string $command the command `fromPayload()` builds from the attributes, which documents them
     */
    public function __construct(
        public string $type,
        public string $command,
    ) {
    }
}
