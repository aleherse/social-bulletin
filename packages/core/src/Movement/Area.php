<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

enum Area: string
{
    case International = 'international';
    case National = 'national';
    case State = 'state';
    case Province = 'province';
    case Region = 'region';
    case Municipality = 'municipality';
    case Neighborhood = 'neighborhood';
}
