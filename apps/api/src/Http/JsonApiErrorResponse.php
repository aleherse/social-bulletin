<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds a JSON:API error document (https://jsonapi.org/format/#error-objects).
 */
final class JsonApiErrorResponse
{
    /**
     * @param array<string, string> $fieldErrors request field => detail, each reported with a source pointer
     */
    public static function create(int $status, string $title, array $fieldErrors = []): JsonResponse
    {
        $errors = [];

        if ([] === $fieldErrors) {
            $errors[] = [
                'status' => (string) $status,
                'title' => $title,
            ];
        }

        foreach ($fieldErrors as $field => $detail) {
            $errors[] = [
                'status' => (string) $status,
                'title' => $title,
                'detail' => $detail,
                'source' => [
                    'pointer' => '/'.$field,
                ],
            ];
        }

        return new JsonResponse([
            'errors' => $errors,
        ], $status, [
            'Content-Type' => JsonApi::CONTENT_TYPE,
        ]);
    }
}
