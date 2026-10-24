import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const registry = readFileSync('wordpress/plugins/woact-core/includes/class-woact-widget-registry.php', 'utf8');
const runtime = readFileSync('wordpress/plugins/woact-core/assets/page-runtime.js', 'utf8');
const core = readFileSync('wordpress/plugins/woact-core/woact-core.php', 'utf8');

test('widget SDK exposes registration and extension hooks', () => {
  assert.match(core, /function woact_register_widget/);
  assert.match(registry, /woact_register_widgets/);
  assert.match(registry, /woact_widget_runtime_data/);
});

test('registry contains WordPress and WooCommerce packs', () => {
  for (const type of ['post_grid', 'lead_form', 'product_grid', 'add_to_cart', 'checkout', 'orders']) {
    assert.match(registry, new RegExp(`'${type}'`));
    assert.match(runtime, new RegExp(`['\"]${type}['\"]`));
  }
});

test('widget data and assets are resolved only for used types', () => {
  assert.match(registry, /collect_types/);
  assert.match(registry, /required_assets/);
  assert.match(registry, /dataGroup/);
});
