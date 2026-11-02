<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement\Event;

enum MovementChange: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Submitted = 'submitted';
}
