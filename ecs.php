<?php

// ADR-0012: Easy Coding Standard owns formatting and fixable coding-standard rules.
// Architectural rules live in deptrac.yaml, type rules in phpstan.dist.neon.

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/apps/api/src',
        __DIR__ . '/packages/core/src',
    ])
    ->withPreparedSets(psr12: true, common: true)
    ->withPhpCsFixerSets(symfony: true)
    ->withCache(__DIR__ . '/apps/api/var/ecs');
