import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const globalDesign = readFileSync('wordpress/plugins/woact-core/assets/admin-global-design.js', 'utf8');
const inspector = readFileSync('wordpress/plugins/woact-core/assets/admin-design-system.js', 'utf8');
const builder = readFileSync('wordpress/plugins/woact-core/assets/admin-page-builder.js', 'utf8');
const runtime = readFileSync('wordpress/plugins/woact-core/assets/page-runtime.js', 'utf8');
const pages = readFileSync('wordpress/plugins/woact-core/includes/class-woact-pages.php', 'utf8');
const core = readFileSync('wordpress/plugins/woact-core/includes/class-woact-core.php', 'utf8');

test('global design panel owns all reusable token families', () => {
  for (const family of ['colors', 'typography', 'spacing', 'radii', 'shadows', 'styles', 'breakpoints', 'customCss']) assert.match(globalDesign, new RegExp(family));
  assert.match(globalDesign, /window\.WoactGlobalDesign/);
});

test('widgets can connect to tokens and saved style presets', () => {
  for (const field of ['colorToken', 'backgroundToken', 'typographyToken', 'globalStyle']) assert.match(inspector, new RegExp(field));
  assert.match(builder, /changeGlobal/);
  assert.match(builder, /WoactGlobalDesign\.Panel/);
});

test('runtime resolves tokens and exports CSS variables', () => {
  assert.match(runtime, /function token/);
  assert.match(runtime, /globals\.styles/);
  assert.match(runtime, /--woact-color-/);
  assert.match(runtime, /--woact-space-/);
  assert.match(runtime, /--woact-radius-/);
});

test('server persists and sanitizes the design system', () => {
  assert.match(pages, /sanitize_design_system/);
  assert.match(pages, /default_design_system/);
  assert.match(pages, /designSystem/);
  assert.match(pages, /20000/);
});

test('global design script loads before the page builder', () => {
  assert.match(core, /woact-admin-page-builder'.*woact-admin-global-design/s);
});
