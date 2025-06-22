<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
    ])
    ->notName(['_ide_helper*']);

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@Symfony' => true,
        '@PSR1' => true,
        '@PSR2' => true,
        '@PSR12' => true,
        'align_multiline_comment' => ['comment_type' => 'phpdocs_like'],
        'combine_consecutive_unsets' => true,
        'concat_space' => ['spacing' => 'one'],
        'heredoc_to_nowdoc' => true,
        'no_alias_functions' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_return' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'length',
        ],
        'phpdoc_align' => [
            'align' => 'left',
            'tags' => [
                'param',
                'property',
                'return',
                'throws',
                'type',
                'var',
            ],
        ],
        'random_api_migration' => true,
        'ternary_to_null_coalescing' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    ]);
