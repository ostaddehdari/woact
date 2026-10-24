import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const registry = readFileSync('wordpress/plugins/woact-core/includes/class-woact-widget-registry.php', 'utf8');
const runtime = readFileSync('wordpress/plugins/woact-core/assets/page-runtime.js', 'utf8');
const core = readFileSync('wordpress/plugins/woact-core/woact-core.php', 'utf8');
const editor = readFileSync('wordpress/plugins/woact-core/assets/admin-page-builder.js', 'utf8');
const aiExample = JSON.parse(readFileSync('wordpress/plugins/woact-core/examples/woact-ai-page-example.json', 'utf8'));
const layoutEditor = readFileSync('wordpress/plugins/woact-core/assets/admin-layout-builder.js', 'utf8');
const phpCore = readFileSync('wordpress/plugins/woact-core/includes/class-woact-core.php', 'utf8');

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

test('AI page contract can be exported and the example is importable', () => {
  assert.match(editor, /JSON برای هوش مصنوعی/);
  assert.match(editor, /woact-page-ai-request/);
  assert.equal(aiExample.product, 'woact-page');
  assert.equal(aiExample.schemaVersion, 1);
  assert.ok(aiExample.document.nodes.length >= 4);
});

test('WordPress posts open directly in Woact and layouts have a library', () => {
  assert.match(phpCore, /ویرایش با Woact/);
  assert.match(phpCore, /page_row_actions/);
  assert.match(editor, /initialPostId/);
  assert.match(editor, /if \(requested\) load\(requested\)/);
  assert.doesNotMatch(editor, /ساخت اولین صفحه/);
  assert.match(layoutEditor, /woact-layout-library/);
  assert.match(layoutEditor, /JSON کامل برای هوش مصنوعی/);
});
