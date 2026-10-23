(function (wp) {
  'use strict';
  if (!wp || !wp.element) return;

  var el = wp.element;
  var h = el.createElement;
  var useEffect = el.useEffect;
  var useState = el.useState;

  function visible(node, device) {
    var value = (node && node.visibility) || {};
    return value[device || 'desktop'] !== false;
  }

  function styleOf(element) {
    var style = element.style || {};
    return {
      color: style.color || undefined,
      background: style.background || undefined,
      fontSize: style.fontSize ? style.fontSize + 'px' : undefined,
      padding: style.padding ? style.padding + 'px' : undefined,
      borderRadius: style.radius ? style.radius + 'px' : undefined,
    };
  }

  function simpleLines(value) {
    return String(value || '').split(/\r?\n/).map(function (line) {
      var parts = line.split('|');
      return { label: parts[0] || '', url: parts[1] || '#' };
    }).filter(function (item) { return item.label; });
  }

  function ElementView(props) {
    var element = props.element || {};
    var p = element.props || {};
    var data = props.data || {};
    var site = data.site || {};
    var commerce = data.commerce || {};
    var className = 'woact-layout-element woact-layout-element--' + element.type;
    var common = { className: className, style: styleOf(element), 'data-woact-element': element.type };

    if (element.type === 'logo') {
      return h('a', Object.assign({}, common, { href: site.url || '/' }), site.logo
        ? h('img', { src: site.logo, alt: p.text || site.name || '', style: { width: (p.width || 150) + 'px' } })
        : h('strong', null, p.text || site.name || 'Woact'));
    }
    if (element.type === 'menu') {
      var items = ((data.menus || {})[p.location || 'primary'] || []).filter(function (item) { return !item.parentId; });
      return h('nav', Object.assign({}, common, { 'aria-label': 'منو' }),
        h('button', { type: 'button', className: 'woact-layout-menu-toggle', 'aria-expanded': 'false', onClick: function (event) {
          var nav = event.currentTarget.parentNode;
          var open = nav.classList.toggle('is-open');
          event.currentTarget.setAttribute('aria-expanded', open ? 'true' : 'false');
        } }, '☰'),
        h('ul', null, items.map(function (item) { return h('li', { key: item.id }, h('a', { href: item.url }, item.label)); }))
      );
    }
    if (element.type === 'search') {
      return h('form', Object.assign({}, common, { role: 'search', action: site.url || '/' }),
        h('input', { type: 'search', name: 's', placeholder: p.placeholder || 'جست‌وجو…' }),
        h('button', { type: 'submit', 'aria-label': 'جست‌وجو' }, '⌕'));
    }
    if (element.type === 'button') return h('a', Object.assign({}, common, { className: className + ' woact-layout-button is-' + (p.variant || 'primary'), href: p.url || '#' }), p.label || 'دکمه');
    if (element.type === 'text') return h('span', common, p.content || '');
    if (element.type === 'html') return h('div', Object.assign({}, common, { dangerouslySetInnerHTML: { __html: p.content || '' } }));
    if (element.type === 'icon') return h(p.url ? 'a' : 'span', Object.assign({}, common, p.url ? { href: p.url } : {}), p.icon || '★');
    if (element.type === 'social') return h('div', common, simpleLines(p.items).map(function (item, index) { return h('a', { key: index, href: item.url, rel: 'noopener noreferrer' }, item.label); }));
    if (element.type === 'language') return h('button', Object.assign({}, common, { type: 'button' }), p.label || 'زبان');
    if (element.type === 'breadcrumb') return h('nav', Object.assign({}, common, { 'aria-label': 'مسیر راهنما' }), h('a', { href: site.url || '/' }, 'خانه'));
    if (element.type === 'spacer') return h('span', Object.assign({}, common, { 'aria-hidden': 'true', style: { width: (p.size || 24) + 'px' } }));
    if (element.type === 'divider') return h('span', Object.assign({}, common, { 'aria-hidden': 'true', style: { borderColor: p.color || '#e2e8f0' } }));
    if (['account', 'cart', 'wishlist', 'compare'].indexOf(element.type) !== -1) {
      var urlKey = element.type + 'Url';
      return h('a', Object.assign({}, common, { href: commerce[urlKey] || '#' }), p.label || element.type,
        element.type === 'cart' && p.showCount ? h('span', { className: 'woact-layout-badge' }, commerce.cartCount || 0) : null);
    }
    if (element.type === 'categories') return h('button', Object.assign({}, common, { type: 'button' }), p.label || 'دسته‌بندی‌ها');
    if (element.type === 'copyright') return h('span', common, String(p.text || '© {year} {site_name}').replace('{year}', new Date().getFullYear()).replace('{site_name}', site.name || 'Woact'));
    if (element.type === 'newsletter') return h('form', common, h('strong', null, p.title || ''), h('input', { type: 'email', placeholder: p.placeholder || '' }), h('button', { type: 'submit' }, 'عضویت'));
    if (element.type === 'contact') return h('div', common, p.text || '');
    if (element.type === 'trust_badges' || element.type === 'payments') return h('span', common, p.text || '');
    if (element.type === 'widget') return h('div', common, 'ناحیه ابزارک: ' + (p.sidebar || 'footer-1'));
    return null;
  }

  function Layout(props) {
    var payload = props.payload || {};
    var document = payload.document || {};
    var settings = document.settings || {};
    var _device = useState(function () {
      if (props.device) return props.device;
      if (window.innerWidth <= 480) return 'mobile';
      if (window.innerWidth <= 782) return 'tablet';
      return 'desktop';
    });
    var device = props.device || _device[0];
    var setAutoDevice = _device[1];
    useEffect(function () {
      if (props.device) return undefined;
      function updateDevice() { setAutoDevice(window.innerWidth <= 480 ? 'mobile' : window.innerWidth <= 782 ? 'tablet' : 'desktop'); }
      window.addEventListener('resize', updateDevice);
      return function () { window.removeEventListener('resize', updateDevice); };
    }, [props.device]);
    var tag = payload.type === 'footer' ? 'footer' : 'header';
    var classes = ['woact-layout', 'woact-layout--' + tag, settings.sticky ? 'is-sticky' : '', settings.shrinkOnScroll ? 'can-shrink' : '', settings.className || ''].filter(Boolean).join(' ');
    return h(tag, { className: classes, 'data-woact-layout-id': payload.id || '', 'data-device': device }, (document.rows || []).map(function (row) {
      if (!visible(row, device)) return null;
      var rs = row.style || {};
      var rowStyle = { '--woact-row-bg': rs.background || '#fff', '--woact-row-color': rs.color || '#17202a', '--woact-row-height': (rs.minHeight || 64) + 'px', '--woact-row-gap': (rs.gap || 20) + 'px' };
      return h('div', { className: 'woact-layout-row ' + (rs.width === 'full' ? 'is-full' : 'is-contained') + (rs.sticky ? ' is-row-sticky' : '') + (rs.overlay ? ' is-overlay' : ''), style: rowStyle, key: row.id },
        h('div', { className: 'woact-layout-row__inner' }, (row.columns || []).map(function (column) {
          return h('div', { className: 'woact-layout-column align-' + (column.align || 'start'), style: { '--woact-column': column.width || 12 }, key: column.id }, (column.elements || []).map(function (element) {
            return visible(element, device) ? h(ElementView, { key: element.id, element: element, data: payload.data || {} }) : null;
          }));
        }))
      );
    }));
  }

  window.WoactLayoutRenderer = { Layout: Layout, Element: ElementView };

  function mountRoots() {
    if (document.body && document.body.classList.contains('woact-render-react')) return;
    var bootstrap = window.WoactLayoutsBootstrap || {};
    ['header', 'footer'].forEach(function (type) {
      var root = document.getElementById(type === 'header' ? 'woact-header-root' : 'woact-footer-root');
      if (!root || !bootstrap[type]) return;
      var app = h(Layout, { payload: bootstrap[type], device: 'desktop' });
      if (typeof el.createRoot === 'function') el.createRoot(root).render(app);
      else if (typeof el.render === 'function') el.render(app, root);
    });
    function shrink() {
      document.querySelectorAll('.woact-layout.can-shrink').forEach(function (node) { node.classList.toggle('is-shrunk', window.scrollY > 30); });
    }
    window.addEventListener('scroll', shrink, { passive: true });
    shrink();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountRoots);
  else mountRoots();
})(window.wp);
