(function (window) {
  'use strict';
  var renderers = Object.create(null);
  var versions = Object.create(null);

  function valid(type) { return /^[a-z][a-z0-9_]{1,63}$/.test(String(type || '')); }
  function register(type, renderer, options) {
    if (!valid(type) || typeof renderer !== 'function') return false;
    renderers[type] = renderer;
    versions[type] = String((options || {}).version || '1.0.0');
    return true;
  }
  function unregister(type) { delete renderers[type]; delete versions[type]; }
  function has(type) { return typeof renderers[type] === 'function'; }
  function render(type, context) { return has(type) ? renderers[type](context || {}) : null; }
  function manifest() { return Object.keys(renderers).map(function (type) { return { type: type, version: versions[type] }; }); }

  window.WoactWidgetRuntime = { register: register, unregister: unregister, has: has, render: render, manifest: manifest };
})(window);
