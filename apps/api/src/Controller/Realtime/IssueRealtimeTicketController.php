<?php

declare(strict_types=1);

namespace App\Controller\Realtime;

use App\Http\Attribute\JsonApi;
use App\Http\Transformer\RealtimeTicketTransformer;
use App\Realtime\RealtimeTicket;
use App\Realtime\TicketIssuer;
use SocialBulletin\Core\Application\User\Model\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class IssueRealtimeTicketController
{
    public function __construct(
        private TicketIssuer $issuer,
    ) {
    }

    #[JsonApi(RealtimeTicketTransformer::class, 'realtime-tickets', status: Response::HTTP_CREATED)]
    #[Route('/api/realtime-tickets', name: 'api_realtime_tickets_issue', methods: ['POST'])]
    public function __invoke(User $currentUser): RealtimeTicket
    {
        return $this->issuer->issue($currentUser);
    }
}
