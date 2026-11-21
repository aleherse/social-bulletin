<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Helper;

interface EventDispatcher
{
    public function dispatch(DomainEvent ...$events): void;
}
