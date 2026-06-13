<?php

declare(strict_types=1);

use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__.'/apps/api/src',
        __DIR__.'/apps/api/migrations',
        __DIR__.'/packages/core/src',
        __DIR__.'/packages/core/spec',
    ]);

    $ecsConfig->sets([SetList::PSR_12, SetList::SYMPLIFY]);
    $ecsConfig->skip([
        ArrayOpenerAndCloserNewlineFixer::class,
        MethodChainingNewlineFixer::class,
    ]);
    $ecsConfig->lineEnding("\n");
};
