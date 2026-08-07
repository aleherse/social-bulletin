<?php

declare(strict_types=1);

namespace App\Controller;

final class RequestPayload
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function stringField(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        return \is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function nullableStringField(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return \is_string($value) ? $value : null;
    }
}
