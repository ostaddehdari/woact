<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('WOACT_TESTING', true);

function sanitize_hex_color(string $value)
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
}

function esc_url_raw(string $value): string
{
    return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
}

function sanitize_textarea_field(string $value): string
{
    return trim(strip_tags($value));
}

function sanitize_text_field(string $value): string
{
    return trim(strip_tags($value));
}

function sanitize_key(string $value): string
{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower($value));
}

function current_user_can(string $capability): bool
{
    return $capability === 'unfiltered_html';
}

function get_post_types(array $args, string $output): array
{
    return ['post' => 'post', 'page' => 'page', 'product' => 'product', 'attachment' => 'attachment'];
}

require_once dirname(__DIR__, 2) . '/wordpress/plugins/woact-core/includes/class-woact-settings.php';
$schema = require dirname(__DIR__, 2) . '/wordpress/plugins/woact-core/includes/settings-schema.php';
$settings = new Woact_Settings($schema);
$defaults = $settings->get_defaults();

$schema_ids = [];
foreach ($schema['groups'] as $group) {
    foreach ($group['sections'] as $section) {
        foreach ($section['fields'] as $field) {
            if (empty($field['id']) || !array_key_exists('default', $field)) {
                fwrite(STDERR, 'Every schema field must have an id and default value.' . PHP_EOL);
                exit(1);
            }
            $schema_ids[] = $field['id'];
        }
    }
}

if (count($defaults) !== 171 || count($schema_ids) !== count(array_unique($schema_ids))) {
    fwrite(STDERR, 'Expected 171 unique schema fields.' . PHP_EOL);
    exit(1);
}

$clean = $settings->sanitize(array_merge($defaults, [
    'render.mode' => 'invalid-mode',
    'layout.container_width' => 9999,
    'style.primary' => 'javascript:alert(1)',
    'render.post_types' => ['page', 'private_type'],
    'typography.body' => ['family' => '<b>Vazir</b>', 'size' => 500, 'weight' => '950', 'lineHeight' => 10],
]));

$checks = [
    [$clean['render.mode'] === 'hybrid', 'Invalid select must fall back'],
    [$clean['layout.container_width'] === 1600, 'Range must be clamped'],
    [$clean['style.primary'] === '#0f766e', 'Invalid color must fall back'],
    [$clean['render.post_types'] === ['page'], 'Unknown post type must be removed'],
    [$clean['typography.body']['family'] === 'Vazir', 'Typography family must be sanitized'],
    [$clean['typography.body']['size'] === 120, 'Typography size must be clamped'],
    [$clean['typography.body']['weight'] === '400', 'Typography weight must fall back'],
    [$clean['typography.body']['lineHeight'] === 3.0, 'Typography line height must be clamped'],
];

foreach ($checks as [$passed, $message]) {
    if (!$passed) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

fwrite(STDOUT, 'PHP settings schema tests passed with ' . count($defaults) . ' fields.' . PHP_EOL);

