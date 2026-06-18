<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('vendor')
    ->path([
        'src',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,

        // =========================
        // FORMATTING
        // =========================
        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,
        'concat_space' => ['spacing' => 'one'],
        'trailing_comma_in_multiline' => true,

        'binary_operator_spaces' => true,
        'unary_operator_spaces' => true,

        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],

        'no_extra_blank_lines' => true,
        'no_whitespace_in_blank_line' => true,

        // =========================
        // IMPORTS
        // =========================
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        'no_unused_imports' => true,
        'no_leading_import_slash' => true,

        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => false,
            'import_constants' => false,
        ],

        // =========================
        // STRICTNESS (very important)
        // =========================
        'strict_param' => false,
        'strict_comparison' => false,

        // =========================
        // PHPDOC
        // =========================
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'phpdoc_align' => false,
        'phpdoc_order' => true,
        'phpdoc_var_without_name' => true,
        'phpdoc_single_line_var_spacing' => true,
        'no_superfluous_phpdoc_tags' => [
            'remove_inheritdoc' => false,
        ],

        // =========================
        // CLASS STRUCTURE
        // =========================
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],

        'ordered_class_elements' => [
            'order' => [
                'use_trait',

                'constant_public',
                'constant_protected',
                'constant_private',

                'property_public',
                'property_protected',
                'property_private',

                'construct',

                'method_public',
                'method_protected',
                'method_private',
            ],
        ],

        'visibility_required' => true,

        // =========================
        // CODE CLEANUP
        // =========================
        'no_useless_else' => true,
        'no_superfluous_elseif' => true,
        'no_unneeded_braces' => true,
    ])
    ->setFinder($finder)
    ;
