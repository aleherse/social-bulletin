<?php

// ADR-0012: Easy Coding Standard owns formatting and fixable coding-standard rules.
// Architectural rules live in deptrac.yaml, type rules in phpstan.dist.neon.
// The two comment rules below are the mechanical half of Constitution Principle V;
// judging whether a comment merely narrates its code stays a review call.

declare(strict_types=1);

use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\LanguageConstruct\NullableTypeDeclarationFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\PHP\CommentedOutCodeSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/apps/api/src',
        __DIR__ . '/apps/api/tests',
        __DIR__ . '/packages/core/src',
        __DIR__ . '/packages/core/spec',
    ])
    ->withRules([CommentedOutCodeSniff::class])
    ->withConfiguredRule(NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true,
        'remove_inheritdoc' => true,
    ])
    ->withPreparedSets(psr12: true, common: true)
    // ECS 13.3 dropped the PHP-CS-Fixer @Symfony set; keep its two visible conventions explicitly.
    ->withConfiguredRule(YodaStyleFixer::class, [
        'equal' => true,
        'identical' => true,
        'less_and_greater' => false,
    ])
    ->withConfiguredRule(ConcatSpaceFixer::class, ['spacing' => 'none'])
    // PhpSpec's spec loader rejects `?type` parameters, so specs keep `type|null`.
    ->withSkip([
        NullableTypeDeclarationFixer::class => [__DIR__ . '/packages/core/spec'],
    ])
    ->withCache(__DIR__ . '/apps/api/var/ecs');
