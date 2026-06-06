<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/../../packages/core/src',
        __DIR__ . '/../../packages/core/spec',
    ]);

    $ecsConfig->sets([
        \Symplify\EasyCodingStandard\ValueObject\Set\SetList::PSR_12,
        \Symplify\EasyCodingStandard\ValueObject\Set\SetList::CLEAN_CODE,
    ]);
};
