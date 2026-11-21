<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Event;

use SocialBulletin\Core\Application\Helper\Notification;

final readonly class MovementChanged implements Notification
{
    public function __construct(
        public MovementChange $change,
        public string $movementId,
        public string $authorId,
    ) {
    }

    public static function channelFor(string $authorId): string
    {
        return '/users/'.$authorId.'/movements';
    }

    public function channel(): string
    {
        return self::channelFor($this->authorId);
    }

    public function payload(): array
    {
        return [
            'change' => $this->change->value,
            'movementId' => $this->movementId,
        ];
    }
}
