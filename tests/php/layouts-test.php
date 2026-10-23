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
function wp_generate_uuid4(): string { static $i = 0; $i++; return sprintf('00000000-0000-4000-8000-%012d', $i); }

require_once dirname(__DIR__, 2) . '/wordpress/plugins/woact-core/includes/class-woact-layouts.php';

$layouts = new Woact_Layouts();
$clean = $layouts->sanitize_document([
    'settings' => ['mobileBreakpoint' => 5000, 'sticky' => true, 'className' => 'safe<script>'],
    'rows' => [[
        'id' => 'row<>1',
        'style' => ['width' => 'invalid', 'minHeight' => 900, 'gap' => -4, 'background' => 'red', 'color' => '#ABCDEF', 'shapeBottom' => ['type' => 'wave-triple', 'color' => '#123456', 'height' => 900, 'flip' => true]],
        'columns' => [[
            'id' => 'column-1',
            'width' => 50,
            'align' => 'invalid',
            'elements' => [
                ['id' => 'valid-1', 'type' => 'button', 'props' => ['label' => '<b>خرید</b>', 'url' => 'javascript:alert(1)'], 'style' => ['fontSize' => 999]],
                ['id' => 'bad-1', 'type' => 'script', 'props' => []],
            ],
        ]],
    ]],
], 'header');

$checks = [
    [$clean['schemaVersion'] === 2, 'Schema version must be present'],
    [$clean['settings']['mobileBreakpoint'] === 1024, 'Breakpoint must be clamped'],
    [$clean['settings']['className'] === 'safescript', 'Class name must be sanitized'],
    [$clean['rows'][0]['id'] === 'row1', 'Node id must be sanitized'],
    [$clean['rows'][0]['style']['width'] === 'contained', 'Invalid width must fall back'],
    [$clean['rows'][0]['style']['minHeight'] === 500, 'Row height must be clamped'],
    [$clean['rows'][0]['style']['background'] === '#ffffff', 'Invalid background must fall back'],
    [$clean['rows'][0]['style']['color'] === '#abcdef', 'Color must be normalized'],
    [$clean['rows'][0]['style']['shapeBottom']['type'] === 'wave-triple', 'Known shape must be preserved'],
    [$clean['rows'][0]['style']['shapeBottom']['height'] === 240, 'Shape height must be clamped'],
    [$clean['rows'][0]['columns'][0]['width'] === 12, 'Column width must be clamped'],
    [count($clean['rows'][0]['columns'][0]['elements']) === 1, 'Unknown elements must be removed'],
    [$clean['rows'][0]['columns'][0]['elements'][0]['props']['label'] === 'خرید', 'Labels must be sanitized'],
    [$clean['rows'][0]['columns'][0]['elements'][0]['props']['url'] === '', 'Unsafe URL must be removed'],
    [$clean['rows'][0]['columns'][0]['elements'][0]['style']['fontSize'] === 96, 'Font size must be clamped'],
];

foreach ($checks as [$passed, $message]) {
    if (!$passed) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

fwrite(STDOUT, 'PHP layout document tests passed.' . PHP_EOL);
