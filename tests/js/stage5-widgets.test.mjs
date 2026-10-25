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
const pagesPhp = readFileSync('wordpress/plugins/woact-core/includes/class-woact-pages.php', 'utf8');
const pagesRestPhp = readFileSync('wordpress/plugins/woact-core/includes/class-woact-pages-rest-controller.php', 'utf8');
const widgetRuntime = readFileSync('wordpress/plugins/woact-core/assets/widget-runtime-registry.js', 'utf8');
const packagedWidget = readFileSync('wordpress/plugins/woact-core/widgets/notice-card/widget.php', 'utf8');
const packagedRenderer = readFileSync('wordpress/plugins/woact-core/widgets/notice-card/runtime.js', 'utf8');
const scaffold = readFileSync('scripts/create-widget.mjs', 'utf8');

test('widget SDK exposes registration and extension hooks', () => {
  assert.match(core, /function woact_register_widget/);
  assert.match(registry, /woact_register_widgets/);
  assert.match(registry, /woact_widget_runtime_data/);
});

test('stage 5 supports self-contained, versioned widget packages', () => {
  assert.match(registry, /discover_packages/);
  assert.match(registry, /glob\(\$root \. '\*\/widget\.php'/);
  assert.match(registry, /migrate_props/);
  assert.match(registry, /register_resolver/);
  assert.match(registry, /enqueue_assets/);
  assert.match(widgetRuntime, /register: register/);
  assert.match(runtime, /WoactWidgetRuntime\.render/);
  assert.match(packagedWidget, /'type' => 'notice_card'/);
  assert.match(packagedWidget, /'migrations'/);
  assert.match(packagedRenderer, /runtime\.register\('notice_card'/);
  assert.match(scaffold, /widgets.*slug/s);
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

test('legacy WordPress content with empty Woact revision meta is safe', () => {
  assert.match(pagesPhp, /is_array\(\$revisions\).*array_filter\(\$revisions, 'is_array'\)/s);
  assert.match(pagesRestPhp, /array_filter\(\(array\).*'is_array'\)/s);
});

test('invalid or Persian AI node ids produce stable revision tokens', () => {
  assert.match(pagesPhp, /sanitize_node\(\$node, 0, \(string\) \$index\)/);
  assert.match(pagesPhp, /stable_node_id/);
  assert.match(pagesPhp, /hash\('sha256', \$path \. ':' \. \$type\)/);
  assert.doesNotMatch(pagesPhp, /'id' => \$this->clean_id\(\(string\) \(\$node\['id'\]/);
});

test('revision mismatch is archived and never blocks publishing with 409', () => {
  assert.match(pagesPhp, /\$conflict_resolved = \$expected_revision/);
  assert.match(pagesPhp, /archive_document\(\$post_id, \(array\) \$current\['draft'\]/);
  assert.match(pagesPhp, /\$saved\['conflictResolved'\] = \$conflict_resolved/);
  assert.doesNotMatch(pagesPhp, /WP_Error\('woact_page_conflict'/);
  assert.match(editor, /نسخهٔ سرور در تاریخچه نگه‌داری/);
});

test('page saves use the WordPress nonce middleware and cannot overlap', () => {
  assert.doesNotMatch(editor, /apiFetch\.use\(apiFetch\.createNonceMiddleware/);
  assert.match(editor, /saveInFlight = useRef\(null\)/);
  assert.match(editor, /if \(saveInFlight\.current\) return saveInFlight\.current/);
  assert.doesNotMatch(editor, /setTimeout\(function \(\) \{ save\(true\); \}, 12000\)/);
  assert.match(editor, /\.catch\(function \(error\).*انتشار انجام نشد/s);
});

test('stage 1 editor shell matches the unified Elementor workflow', () => {
  for (const feature of ['woact-unified-panel', 'woact-navigator-float', 'woact-history-float', 'woact-context-menu', 'woact-panel-switcher']) {
    assert.match(editor, new RegExp(feature));
  }
  assert.match(editor, /\['content', 'محتوا'\].*\['style', 'استایل'\].*\['advanced', 'پیشرفته'\]/s);
  assert.match(editor, /localStorage\.setItem\('woact-page-backup-'/);
  assert.match(editor, /key === 's'/);
  assert.match(editor, /key === 'p'/);
});
