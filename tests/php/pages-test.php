<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('WOACT_TESTING', true);
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_key(string $value): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)) ?: ''; }
function sanitize_html_class(string $value): string { return preg_replace('/[^a-zA-Z0-9_-]/', '', $value) ?: ''; }
function sanitize_hex_color(string $value) { return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null; }
function esc_url_raw(string $value): string { return filter_var($value, FILTER_VALIDATE_URL) ? $value : ''; }
function wp_kses_post(string $value): string { return strip_tags($value, '<strong><em><a>'); }
function wp_generate_uuid4(): string { static $i = 0; return sprintf('00000000-0000-4000-8000-%012d', ++$i); }

require_once dirname(__DIR__, 2) . '/wordpress/plugins/woact-core/includes/class-woact-pages.php';
$pages = new Woact_Pages();
$clean = $pages->sanitize_document(['settings' => ['contentWidth' => 9000, 'background' => 'red'], 'nodes' => [[
    'id' => 'section<>1', 'type' => 'section', 'props' => ['tag' => 'section'],
    'style' => ['padding' => 999, 'background' => '#ABCDEF'], 'children' => [[
        'id' => 'title-1', 'type' => 'heading', 'props' => ['text' => '<b>عنوان</b>', 'level' => 'h1'],
        'style' => ['fontSize' => 999], 'children' => [],
    ], ['id' => 'bad', 'type' => 'script']],
]]]);
$checks = [
    [$clean['schemaVersion'] === 1, 'Schema version'],
    [$clean['settings']['contentWidth'] === 1920, 'Content width clamp'],
    [$clean['settings']['background'] === '#ffffff', 'Background fallback'],
    [$clean['nodes'][0]['id'] === 'section1', 'Stable sanitized id'],
    [$clean['nodes'][0]['style']['padding'] === 200, 'Spacing clamp'],
    [count($clean['nodes'][0]['children']) === 1, 'Unknown node removed'],
    [$clean['nodes'][0]['children'][0]['props']['text'] === 'عنوان', 'Text sanitized'],
    [$clean['nodes'][0]['children'][0]['style']['fontSize'] === 150, 'Font size clamp'],
];
foreach ($checks as [$passed, $message]) { if (!$passed) { fwrite(STDERR, $message . PHP_EOL); exit(1); } }
fwrite(STDOUT, "PHP page document tests passed.\n");
