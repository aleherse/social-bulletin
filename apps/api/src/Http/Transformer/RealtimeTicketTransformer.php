<?php

declare(strict_types=1);

namespace App\Http\Transformer;

use App\Realtime\RealtimeTicket;
use League\Fractal\TransformerAbstract;

final class RealtimeTicketTransformer extends TransformerAbstract
{
    /**
     * @return array{id: string, ticket: string, expiresAt: string, endpoint: string, channels: list<string>}
     */
    public function transform(RealtimeTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket' => $ticket->ticket,
            'expiresAt' => $ticket->expiresAt->format(\DateTimeInterface::ATOM),
            'endpoint' => $ticket->endpoint,
            'channels' => $ticket->channels,
        ];
    }
}
