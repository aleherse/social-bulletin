<?php

// ADR-0012: Easy Coding Standard owns formatting and fixable coding-standard rules.
// Architectural rules live in deptrac.yaml, type rules in phpstan.dist.neon.
// The two comment rules below are the mechanical half of Constitution Principle V;
// judging whether a comment merely narrates its code stays a review call.

declare(strict_types=1);

use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\PHP\CommentedOutCodeSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/apps/api/src',
        __DIR__ . '/packages/core/src',
    ])
    ->withRules([CommentedOutCodeSniff::class])
    ->withConfiguredRule(NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true,
        'remove_inheritdoc' => true,
    ])
    ->withPreparedSets(psr12: true, common: true)
    ->withPhpCsFixerSets(symfony: true)
    ->withCache(__DIR__ . '/apps/api/var/ecs');
