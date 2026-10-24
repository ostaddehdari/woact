(function (wp) {
  'use strict';
  if (!wp || !wp.element) return;
  var h = wp.element.createElement;

  function visible(node, device) {
    return !node.visibility || node.visibility[device || 'desktop'] !== false;
  }
  function resolvedStyle(node, device) {
    var base = node.style || {};
    var override = device === 'desktop' ? {} : ((node.responsive || {})[device] || {});
    var s = Object.assign({}, base, override);
    var justify = { start: 'flex-start', center: 'center', end: 'flex-end', between: 'space-between' };
    var align = { start: 'flex-start', center: 'center', end: 'flex-end', stretch: 'stretch' };
    return {
      display: s.display || undefined, flexDirection: s.direction || undefined, gap: s.gap !== undefined ? s.gap + 'px' : undefined,
      padding: s.padding !== undefined ? s.padding + 'px' : undefined, margin: s.margin !== undefined ? s.margin + 'px' : undefined,
      background: s.background || undefined, color: s.color || undefined, width: s.width ? s.width + 'px' : undefined,
      maxWidth: s.maxWidth ? s.maxWidth + 'px' : undefined, minHeight: s.minHeight ? s.minHeight + 'px' : undefined,
      alignItems: align[s.align] || undefined, justifyContent: justify[s.justify] || undefined,
      fontSize: s.fontSize ? s.fontSize + 'px' : undefined, fontWeight: s.fontWeight || undefined,
      borderRadius: s.radius ? s.radius + 'px' : undefined, borderWidth: s.borderWidth ? s.borderWidth + 'px' : undefined,
      borderColor: s.borderColor || undefined, borderStyle: s.borderWidth ? 'solid' : undefined,
      boxShadow: s.shadow || undefined, zIndex: s.zIndex || undefined,
    };
  }
  function get(source, data) {
    if (!source) return undefined;
    return source.split('.').reduce(function (value, key) { return value && value[key] !== undefined ? value[key] : undefined; }, data || {});
  }
  function Node(props) {
    var node = props.node || {};
    var p = node.props || {};
    var device = props.device || 'desktop';
    if (!visible(node, device)) return null;
    var dynamic = get(node.dynamic, props.data);
    var style = resolvedStyle(node, device);
    var common = { className: 'woact-page-node woact-page-node--' + node.type, style: style, 'data-woact-node': node.id };
    var children = (node.children || []).map(function (child) { return h(Node, { key: child.id, node: child, device: device, data: props.data }); });
    if (node.type === 'section') return h(['section', 'header', 'footer', 'main', 'article', 'aside'].indexOf(p.tag) >= 0 ? p.tag : 'section', common, children);
    if (node.type === 'container' || node.type === 'columns') return h('div', common, children);
    if (node.type === 'column') return h('div', Object.assign({}, common, { style: Object.assign({}, style, { flexBasis: ((p.basis || 6) / 12 * 100) + '%' }) }), children);
    if (node.type === 'heading') { var level = /^h[1-6]$/.test(p.level) ? p.level : 'h2'; return h(level, common, dynamic !== undefined ? dynamic : p.text); }
    if (node.type === 'text') return h('p', common, dynamic !== undefined ? dynamic : p.text);
    if (node.type === 'button') return h('a', Object.assign({}, common, { className: common.className + ' is-' + (p.variant || 'primary'), href: p.url || '#' }), p.label || 'دکمه');
    if (node.type === 'image') { var src = dynamic !== undefined ? dynamic : p.url; return h('figure', common, src ? h('img', { src: src, alt: p.alt || '', loading: 'lazy' }) : h('span', { className: 'woact-page-image-placeholder' }, 'تصویر'), p.caption ? h('figcaption', null, p.caption) : null); }
    if (node.type === 'icon') return h('span', common, p.icon || '★');
    if (node.type === 'list') return h('ul', common, String(p.items || '').split(/\r?\n/).filter(Boolean).map(function (item, index) { return h('li', { key: index }, item); }));
    if (node.type === 'quote') return h('blockquote', common, h('p', null, p.text || ''), p.author ? h('cite', null, p.author) : null);
    if (node.type === 'divider') return h('hr', common);
    if (node.type === 'spacer') return h('div', Object.assign({}, common, { style: Object.assign({}, style, { height: (p.height || 40) + 'px' }) }));
    if (node.type === 'html') return h('div', Object.assign({}, common, { dangerouslySetInnerHTML: { __html: p.html || '' } }));
    return null;
  }
  function Page(props) {
    var document = props.document || {};
    var settings = document.settings || {};
    return h('div', { className: 'woact-page-runtime ' + (settings.className || ''), style: { '--woact-page-width': (settings.contentWidth || 1180) + 'px', background: settings.background || '#fff' }, 'data-device': props.device || 'desktop' },
      (document.nodes || []).map(function (node) { return h(Node, { key: node.id, node: node, device: props.device || 'desktop', data: props.data || {} }); })
    );
  }
  window.WoactPageRenderer = { Page: Page, Node: Node, styleOf: resolvedStyle };
})(window.wp);
