<?php

declare(strict_types=1);

namespace App\Http;

/**
 * The request body is not a JSON:API resource document the endpoint accepts.
 */
final class InvalidJsonApiRequest extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $title,
    ) {
        parent::__construct($title);
    }
}
