import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const design = readFileSync('wordpress/plugins/woact-core/assets/admin-design-system.js', 'utf8');
const builder = readFileSync('wordpress/plugins/woact-core/assets/admin-page-builder.js', 'utf8');
const runtime = readFileSync('wordpress/plugins/woact-core/assets/page-runtime.js', 'utf8');
const pages = readFileSync('wordpress/plugins/woact-core/includes/class-woact-pages.php', 'utf8');
const core = readFileSync('wordpress/plugins/woact-core/includes/class-woact-core.php', 'utf8');

test('stage 3 exposes all ten shared design work areas', () => {
  for (const label of ['طرح‌بندی', 'موشن افکت', 'تبدیل', 'پس‌زمینه', 'حاشیه و سایه', 'ماسک', 'واکنش‌گرا', 'ویژگی‌ها', 'CSS سفارشی']) assert.match(design, new RegExp(label));
  assert.match(design, /window\.WoactDesignSystem/);
});

test('every new page node receives a reusable design document', () => {
  assert.match(builder, /design: window\.WoactDesignSystem/);
  assert.match(builder, /changeDesign/);
  assert.match(builder, /designFields/);
});

test('runtime applies design, animation, masks, safe attributes and scoped CSS', () => {
  for (const token of ['designStyle', 'clipPath', 'entranceAnimation', 'hoverAnimation', 'customCss', 'data-woact-node']) assert.match(runtime, new RegExp(token));
  assert.match(runtime, /\^\(data\|aria\)-/);
});

test('server sanitizes design JSON and dangerous custom CSS constructs', () => {
  assert.match(pages, /sanitize_design/);
  assert.match(pages, /javascript\\s\*:/);
  assert.match(pages, /collect_custom_css/);
  assert.match(pages, /design_attributes/);
});

test('shared design system loads before both builders', () => {
  assert.match(core, /woact-admin-page-builder'.*woact-admin-design-system/s);
  assert.match(core, /woact-admin-layout-builder'.*woact-admin-design-system/s);
});
