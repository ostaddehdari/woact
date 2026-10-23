(function (wp) {
  'use strict';
  if (!wp || !wp.element) return;

  var el = wp.element;
  var h = el.createElement;
  var useEffect = el.useEffect;
  var useState = el.useState;

  var SHAPE_PATHS = {
    wave: ['M0 55 C180 110 360 5 600 55 C840 105 1020 15 1200 55 L1200 120 L0 120 Z'],
    'wave-soft': ['M0 45 C260 95 420 95 600 55 C810 8 1010 20 1200 72 L1200 120 L0 120 Z'],
    'wave-double': ['M0 42 C220 110 430 10 650 55 C860 98 1030 20 1200 48 L1200 120 L0 120 Z', 'M0 68 C250 18 440 104 680 62 C910 24 1040 82 1200 62 L1200 120 L0 120 Z'],
    'wave-triple': ['M0 38 C180 92 390 8 600 48 C820 90 1000 18 1200 50 L1200 120 L0 120 Z', 'M0 62 C220 18 420 105 650 62 C870 20 1030 86 1200 62 L1200 120 L0 120 Z', 'M0 82 C240 52 440 112 680 78 C920 48 1040 96 1200 82 L1200 120 L0 120 Z'],
    mountain: ['M0 120 L600 8 L1200 120 Z'], mountains: ['M0 120 L210 48 L360 90 L570 18 L760 86 L940 38 L1200 120 Z'],
    peaks: ['M0 120 L150 25 L300 120 L450 18 L600 120 L750 30 L900 120 L1050 14 L1200 120 Z'], triangle: ['M0 0 L600 120 L1200 0 L1200 120 L0 120 Z'],
    'triangle-asym': ['M0 10 L830 120 L1200 42 L1200 120 L0 120 Z'], curve: ['M0 82 Q600 -15 1200 82 L1200 120 L0 120 Z'],
    'curve-asym': ['M0 96 Q340 8 1200 58 L1200 120 L0 120 Z'], tilt: ['M0 92 L1200 12 L1200 120 L0 120 Z'],
    'tilt-split': ['M0 40 L600 96 L1200 28 L1200 120 L0 120 Z'], arrow: ['M0 25 L600 105 L1200 25 L1200 120 L0 120 Z'],
    fan: ['M0 120 L600 10 L420 120 Z M420 120 L600 10 L780 120 Z M780 120 L600 10 L1200 120 Z'],
    clouds: ['M0 84 C35 40 90 45 120 78 C155 28 230 36 248 83 C305 50 360 60 380 92 C450 42 535 48 560 88 C620 35 700 44 720 86 C790 50 850 60 872 90 C935 32 1020 42 1040 84 C1100 52 1160 64 1200 92 L1200 120 L0 120 Z'],
    zigzag: ['M0 70 L100 25 L200 70 L300 25 L400 70 L500 25 L600 70 L700 25 L800 70 L900 25 L1000 70 L1100 25 L1200 70 L1200 120 L0 120 Z'],
    steps: ['M0 82 H200 V62 H400 V42 H600 V62 H800 V82 H1000 V62 H1200 V120 H0 Z'], pyramids: ['M0 120 L150 42 L300 120 L450 42 L600 120 L750 42 L900 120 L1050 42 L1200 120 Z'],
    book: ['M0 35 Q300 110 600 62 Q900 14 1200 35 L1200 120 L0 120 Z'], brush: ['M0 64 L90 45 L180 70 L270 42 L360 72 L450 50 L540 78 L640 40 L735 68 L835 48 L940 74 L1040 44 L1200 65 L1200 120 L0 120 Z'],
    city: ['M0 120 V70 H80 V42 H145 V82 H215 V30 H300 V68 H375 V50 H455 V88 H535 V26 H630 V70 H710 V44 H790 V82 H880 V34 H970 V72 H1060 V48 H1140 V84 H1200 V120 Z'],
    organic: ['M0 72 C110 20 245 110 370 62 C510 8 590 108 735 60 C900 6 1040 112 1200 48 L1200 120 L0 120 Z'],
    droplets: ['M0 48 Q80 100 160 48 T320 48 T480 48 T640 48 T800 48 T960 48 T1120 48 Q1160 72 1200 48 L1200 120 L0 120 Z'],
    torn: ['M0 72 L70 55 L130 78 L210 48 L285 74 L360 52 L445 82 L525 46 L610 76 L700 50 L790 80 L870 48 L960 74 L1045 52 L1120 80 L1200 58 L1200 120 L0 120 Z']
  };

  function ShapeDivider(props) {
    var shape = props.shape || {};
    if (!shape.type || shape.type === 'none' || !SHAPE_PATHS[shape.type]) return null;
    var paths = SHAPE_PATHS[shape.type];
    var classes = ['woact-shape-divider', 'is-' + props.position, shape.flip ? 'is-flipped' : '', shape.invert ? 'is-inverted' : ''].filter(Boolean).join(' ');
    return h('div', { className: classes, style: { '--woact-shape-height': (shape.height || 40) + 'px', color: shape.color || '#fff' } },
      h('svg', { viewBox: '0 0 1200 120', preserveAspectRatio: 'none', 'aria-hidden': 'true' }, paths.map(function (path, index) {
        return h('path', { key: index, d: path, opacity: paths.length > 1 ? (index + 1) / paths.length : 1 });
      }))
    );
  }

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
        h(ShapeDivider, { shape: rs.shapeTop, position: 'top' }),
        h('div', { className: 'woact-layout-row__inner' }, (row.columns || []).map(function (column) {
          return h('div', { className: 'woact-layout-column align-' + (column.align || 'start'), style: { '--woact-column': column.width || 12 }, key: column.id }, (column.elements || []).map(function (element) {
            return visible(element, device) ? h(ElementView, { key: element.id, element: element, data: payload.data || {} }) : null;
          }));
        })),
        h(ShapeDivider, { shape: rs.shapeBottom, position: 'bottom' })
      );
    }));
  }

  window.WoactLayoutRenderer = { Layout: Layout, Element: ElementView, ShapeDivider: ShapeDivider, shapePaths: SHAPE_PATHS };

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
