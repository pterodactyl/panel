<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder)->in([
    'app',
    'bootstrap',
    'config',
    'database',
    'resources/lang',
    'routes',
    'tests',
]);

return (new Config)
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
            'sortAlgorithm' => 'length',
        ],
        'psr0' => ['dir' => 'app'],
        'psr4' => true,
        'random_api_migration' => true,
        'ternary_to_null_coalescing' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    ]);
