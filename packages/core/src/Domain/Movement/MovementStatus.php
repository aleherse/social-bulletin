<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

enum MovementStatus: string
{
    case Draft = 'draft';
    case Proposed = 'proposed';
    case Published = 'published';
}
