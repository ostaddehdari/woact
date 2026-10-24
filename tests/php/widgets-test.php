<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('WOACT_TESTING', true);
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_key(string $value): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)) ?: ''; }

require_once dirname(__DIR__, 2) . '/wordpress/plugins/woact-core/includes/class-woact-widget-registry.php';
$registry = new Woact_Widget_Registry();
$registry->boot();
if (count($registry->definitions()) < 50) { fwrite(STDERR, "Widget pack is incomplete.\n"); exit(1); }
foreach (['hero', 'post_grid', 'lead_form', 'product_grid', 'add_to_cart', 'checkout', 'orders'] as $type) {
    if (!$registry->has($type)) { fwrite(STDERR, "Missing widget: {$type}\n"); exit(1); }
}
$document = ['nodes' => [['type' => 'hero'], ['type' => 'product_grid'], ['type' => 'tabs']]];
$assets = $registry->required_assets($document);
if (!in_array('interactive', $assets, true)) { fwrite(STDERR, "Interactive asset was not collected.\n"); exit(1); }
if (!$registry->register(['type' => 'vendor_card', 'label' => 'Vendor card'])) { fwrite(STDERR, "Public registration failed.\n"); exit(1); }
fwrite(STDOUT, "PHP widget registry tests passed.\n");
