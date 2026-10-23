import test from 'node:test';
import assert from 'node:assert/strict';
import { decideRenderMode } from '../../wordpress/plugins/woact-core/assets/render-policy.mjs';

const base = {
  globalMode: 'hybrid',
  override: 'inherit',
  isBuilderDocument: false,
  preserveBuilders: true,
  postType: 'page',
  enabledPostTypes: ['page', 'post'],
};

test('hybrid mode keeps inherited pages on WordPress', () => {
  assert.equal(decideRenderMode(base), 'classic');
});

test('hybrid mode allows an explicit React page', () => {
  assert.equal(decideRenderMode({ ...base, override: 'react' }), 'react');
});

test('builder documents remain classic in global React mode', () => {
  assert.equal(decideRenderMode({
    ...base,
    globalMode: 'react',
    isBuilderDocument: true,
  }), 'classic');
});

test('explicit React opt-in can migrate a builder document', () => {
  assert.equal(decideRenderMode({
    ...base,
    globalMode: 'react',
    override: 'react',
    isBuilderDocument: true,
  }), 'react');
});

test('classic mode is a site-wide safety switch', () => {
  assert.equal(decideRenderMode({
    ...base,
    globalMode: 'classic',
    override: 'react',
  }), 'classic');
});

test('global React mode only handles enabled post types', () => {
  assert.equal(decideRenderMode({ ...base, globalMode: 'react' }), 'react');
  assert.equal(decideRenderMode({
    ...base,
    globalMode: 'react',
    postType: 'product',
  }), 'classic');
});

