<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Health')]
final class HealthController
{
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    #[OA\Get(
        path: '/health',
        summary: 'Health check',
        description: 'Returns the operational status of the API.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'API is operational',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
