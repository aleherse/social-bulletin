<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Helper;

trait RecordsEvents
{
    /**
     * @var list<DomainEvent>
     */
    private array $events = [];

    /**
     * @return list<DomainEvent>
     */
    public function pullEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    private function recordThat(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
}
