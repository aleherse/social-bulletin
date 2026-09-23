<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Helper;

/**
 * The domain's published error surface: the one part of `Domain` that `apps/api` may
 * name, so a controller can map a failure to a status code without reaching into an
 * aggregate. `deptrac.yaml` collects implementors into their own layer.
 */
interface DomainError extends \Throwable
{
}
