import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const runtime = readFileSync('wordpress/plugins/woact-core/assets/layout-runtime.js', 'utf8');
const runtimeCss = readFileSync('wordpress/plugins/woact-core/assets/layout-runtime.css', 'utf8');
const builder = readFileSync('wordpress/plugins/woact-core/assets/admin-layout-builder.js', 'utf8');
const sample = JSON.parse(readFileSync('wordpress/plugins/woact-core/examples/woact-ai-header-footer-example.json', 'utf8'));

test('builder canvas uses the shared React runtime for live elements', () => {
  assert.match(builder, /WoactLayoutRenderer\.Element/);
  assert.match(builder, /پیش‌نمایش زنده React/);
  assert.match(builder, /runtimeData/);
});

test('runtime exposes SVG shape dividers', () => {
  assert.match(runtime, /function ShapeDivider/);
  assert.match(runtime, /wave-triple/);
  assert.match(runtime, /mountains/);
  assert.match(runtime, /shapeBottom/);
  assert.match(runtimeCss, /\.woact-shape-divider\.is-bottom svg\{transform:rotate\(180deg\)\}/);
  assert.match(runtimeCss, /\.is-bottom\.is-inverted svg\{transform:none\}/);
});

test('AI example imports a header and footer bundle', () => {
  assert.equal(sample.product, 'woact-layout-bundle');
  assert.equal(sample.schemaVersion, 2);
  assert.deepEqual(sample.templates.map((template) => template.type), ['header', 'footer']);
  assert.equal(sample.templates[0].document.rows[1].style.shapeBottom.type, 'wave-triple');
  assert.equal(sample.templates[1].document.rows[0].style.shapeTop.type, 'mountains');
});
