(function (wp, config) {
  'use strict';
  if (!wp || !wp.element || !config || !config.document) return;

  function mount() {
    var root = document.getElementById(config.rootId || '');
    var Page = window.WoactPageRenderer && window.WoactPageRenderer.Page;
    if (!root || !Page) return;
    var view = wp.element.createElement(Page, {
      document: config.document,
      data: config.data || {},
      device: 'desktop',
    });
    if (typeof wp.element.createRoot === 'function') {
      wp.element.createRoot(root).render(view);
    } else if (typeof wp.element.render === 'function') {
      wp.element.render(view, root);
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
  else mount();
})(window.wp, window.WoactPageMount || null);
