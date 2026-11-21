<?php

declare(strict_types=1);

namespace App\Realtime;

final readonly class RealtimeTicket
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        public string $id,
        public string $ticket,
        public \DateTimeImmutable $expiresAt,
        public string $endpoint,
        public array $channels,
    ) {
    }
}
