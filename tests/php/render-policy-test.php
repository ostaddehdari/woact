<?php
declare(strict_types=1);

define('WOACT_TESTING', true);
require_once dirname(__DIR__, 2) . '/wordpress/plugins/woact-core/includes/class-woact-render-policy.php';

function assert_mode(string $expected, array $context, string $message): void
{
    $actual = Woact_Render_Policy::decide($context);
    if ($actual !== $expected) {
        fwrite(STDERR, $message . ": expected {$expected}, got {$actual}\n");
        exit(1);
    }
}

$base = [
    'global_mode' => 'hybrid',
    'override' => 'inherit',
    'is_builder_document' => false,
    'preserve_builders' => true,
    'post_type' => 'page',
    'enabled_post_types' => ['page', 'post'],
];

assert_mode('classic', $base, 'Hybrid defaults to classic');
assert_mode('react', array_merge($base, ['override' => 'react']), 'Explicit React works');
assert_mode('classic', array_merge($base, ['global_mode' => 'react', 'is_builder_document' => true]), 'Builder safety works');
assert_mode('react', array_merge($base, ['global_mode' => 'react', 'is_builder_document' => true, 'override' => 'react']), 'Explicit migration works');
assert_mode('classic', array_merge($base, ['global_mode' => 'classic', 'override' => 'react']), 'Classic safety switch works');

fwrite(STDOUT, "PHP render policy tests passed.\n");

