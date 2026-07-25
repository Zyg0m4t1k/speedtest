<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'resources',
        'data',
        '.venv',
        'tmp',
        'node_modules',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setUsingCache(true)
    ->setRules([
        '@PSR12' => true,

        // Structure propre
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'single_blank_line_at_eof' => true,

        // Gestion des lignes vides / espaces
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
                'use_trait',
                'return',
                'break',
                'continue',
                'curly_brace_block',
                'parenthesis_brace_block',
                'square_brace_block',
            ],
        ],
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,

        // 1 ligne entre const / propriétés / méthodes
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'property' => 'one',
                'method' => 'one',
            ],
        ],

        // Accolades (options compatibles avec ta version)
        'braces_position' => [
            'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'control_structures_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],

        // Indentation
        'indentation_type' => true,
    ])
    ->setFinder($finder);
