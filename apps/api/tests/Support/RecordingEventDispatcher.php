<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\DBAL\Connection;
use SocialBulletin\Core\Domain\Helper\DomainEvent;
use SocialBulletin\Core\Domain\Helper\EventDispatcher;
use SocialBulletin\Core\Domain\Movement\Events\MovementHasBeenCreated;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

final class RecordingEventDispatcher implements EventDispatcher
{
    /**
     * @var list<DomainEvent>
     */
    private array $dispatched = [];

    /**
     * @var list<string> ids of the movements whose row existed when their creation was dispatched
     */
    private array $movementsStoredBeforeDispatch = [];

    public function __construct(
        #[AutowireDecorated]
        private readonly EventDispatcher $inner,
        private readonly Connection $connection,
    ) {
    }

    public function dispatch(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatched[] = $event;

            if ($event instanceof MovementHasBeenCreated && $this->movementExists((string) $event->movementId)) {
                $this->movementsStoredBeforeDispatch[] = (string) $event->movementId;
            }
        }

        $this->inner->dispatch(...$events);
    }

    /**
     * @return list<DomainEvent>
     */
    public function dispatched(): array
    {
        return $this->dispatched;
    }

    public function wasStoredBeforeDispatch(string $movementId): bool
    {
        return in_array($movementId, $this->movementsStoredBeforeDispatch, true);
    }

    private function movementExists(string $id): bool
    {
        return false !== $this->connection->fetchOne('SELECT 1 FROM bulletin.movements WHERE id = :id', [
            'id' => $id,
        ]);
    }
}
