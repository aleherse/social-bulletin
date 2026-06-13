<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\PsrAutoloadingFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/apps/api/src',
        __DIR__ . '/packages/core/src',
    ]);

    $ecsConfig->rule(PsrAutoloadingFixer::class);
};
