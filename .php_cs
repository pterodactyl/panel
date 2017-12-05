<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        'app',
        'bootstrap',
        'config',
        'database',
        'resources/lang',
        'routes',
        'tests',
    ]);

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        '@PSR1' => true,
        '@PSR2' => true,
        'align_multiline_comment' => ['comment_type' => 'phpdocs_like'],
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_return' => true,
        'blank_line_before_statement' => false,
        'combine_consecutive_unsets' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'single'],
        'heredoc_to_nowdoc' => true,
        'increment_style' => ['style' => 'post'],
        'linebreak_after_opening_tag' => true,
        'method_argument_space' => [
            'ensure_fully_multiline' => false,
            'keep_multiple_spaces_after_comma' => false,
        ],
        'new_with_braces' => false,
        'no_alias_functions' => true,
        'no_multiline_whitespace_before_semicolons' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_return' => true,
        'not_operator_with_successor_space' => true,
        'ordered_imports' => [
            'sortAlgorithm' => 'length',
        ],
        'phpdoc_align' => ['tags' => ['param']],
        'phpdoc_separation' => false,
        'protected_to_private' => false,
        'psr0' => ['dir' => 'app'],
        'psr4' => true,
        'random_api_migration' => true,
        'standardize_not_equals' => true,
        'ternary_to_null_coalescing' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    ])->setRiskyAllowed(true)->setFinder($finder);
