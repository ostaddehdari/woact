import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const editor = readFileSync('wordpress/plugins/woact-core/assets/admin-page-builder.js', 'utf8');
const runtime = readFileSync('wordpress/plugins/woact-core/assets/page-runtime.js', 'utf8');
const mount = readFileSync('wordpress/plugins/woact-core/assets/page-mount.js', 'utf8');
const app = readFileSync('wordpress/themes/woact/assets/js/react-app.js', 'utf8');
const core = readFileSync('wordpress/plugins/woact-core/includes/class-woact-core.php', 'utf8');

test('page builder uses shared live React runtime', () => {
  assert.match(editor, /WoactPageRenderer\.Node/);
  assert.match(editor, /بوم زنده React/);
  assert.match(editor, /text\/woact-page/);
});

test('page runtime supports nested nodes and responsive styles', () => {
  assert.match(runtime, /function Node/);
  assert.match(runtime, /node\.responsive/);
  assert.match(runtime, /node\.children/);
  assert.match(runtime, /window\.WoactPageRenderer/);
});

test('theme renders published page documents', () => {
  assert.match(app, /data\.pageDocument/);
  assert.match(app, /WoactPageRenderer/);
});

test('published React pages mount inside any active WordPress theme', () => {
  assert.match(core, /add_filter\('the_content', \[\$this, 'render_react_page_content'\], 999\)/);
  assert.match(core, /data-woact-page-root/);
  assert.match(core, /wp_enqueue_script\('woact-page-mount'/);
  assert.match(core, /window\.WoactPageMount=/);
  assert.match(mount, /WoactPageRenderer/);
  assert.match(mount, /createRoot\(root\)\.render\(view\)/);
});
