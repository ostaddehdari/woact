import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const controls = readFileSync('wordpress/plugins/woact-core/assets/admin-controls.js', 'utf8');
const schema = readFileSync('wordpress/plugins/woact-core/includes/class-woact-control-schema.php', 'utf8');
const core = readFileSync('wordpress/plugins/woact-core/includes/class-woact-core.php', 'utf8');
const pages = readFileSync('wordpress/plugins/woact-core/includes/class-woact-pages.php', 'utf8');

test('control registry exposes all stage 2 reusable control families', () => {
  for (const type of ['text', 'slider', 'dimensions', 'color', 'gradient', 'typography', 'background', 'border', 'shadow', 'media', 'repeater', 'nested_repeater']) {
    assert.match(controls, new RegExp(`register\\('${type}'`));
    assert.match(schema, new RegExp(`'${type}'`));
  }
  assert.match(controls, /window\.WoactControls/);
});

test('conditions support logical and comparison operators', () => {
  for (const operator of ['condition.and', 'condition.or', "'not'", "'in'", "'gt'", "'lt'"]) {
    assert.match(controls, new RegExp(operator.replace('.', '\\.')));
  }
});

test('responsive controls inherit desktop values and store per-device overrides', () => {
  assert.match(controls, /if \(schema\.responsive\)/);
  assert.match(controls, /responsive\[device\]/);
  assert.match(controls, /copy\[device\] = next/);
  assert.match(controls, /woact-control-reset/);
});

test('shared controls load before page and layout builders', () => {
  assert.match(core, /woact-admin-page-builder'.*woact-admin-controls/s);
  assert.match(core, /woact-admin-layout-builder'.*woact-admin-controls/s);
  assert.match(pages, /Woact_Control_Schema::manifest\(\)/);
});

test('nested repeater values survive secure page sanitization', () => {
  assert.match(pages, /sanitize_prop_value/);
  assert.match(pages, /is_array\(\$value\)/);
  assert.match(pages, /array_slice\(\$value, 0, 100, true\)/);
});
