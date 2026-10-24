import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const editor = readFileSync('wordpress/plugins/woact-core/assets/admin-page-builder.js', 'utf8');
const runtime = readFileSync('wordpress/plugins/woact-core/assets/page-runtime.js', 'utf8');
const app = readFileSync('wordpress/themes/woact/assets/js/react-app.js', 'utf8');

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
