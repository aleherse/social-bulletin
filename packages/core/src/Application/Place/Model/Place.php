<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Place\Model;

final readonly class Place
{
    public function __construct(
        public ?string $name,
    ) {
    }
}
