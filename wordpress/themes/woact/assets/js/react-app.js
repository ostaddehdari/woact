(function (wp, bootstrap) {
  'use strict';

  if (!wp || !wp.element) {
    return;
  }

  var element = wp.element;
  var h = element.createElement;
  var Fragment = element.Fragment;

  function Navigation(props) {
    var items = Array.isArray(props.items) ? props.items.filter(function (item) {
      return !item.parentId;
    }) : [];

    if (!items.length) return null;

    return h(
      'nav',
      { className: 'woact-react-nav', 'aria-label': 'منوی اصلی' },
      h('ul', null, items.map(function (item) {
        return h(
          'li',
          { key: item.id },
          h('a', { href: item.url, target: item.target || '_self' }, item.label)
        );
      }))
    );
  }

  function Header(props) {
    return h(
      'header',
      { className: 'woact-react-header' },
      h(
        'div',
        { className: 'woact-container woact-react-header__inner' },
        h('a', { className: 'woact-brand', href: props.site.url }, props.site.name),
        h(Navigation, { items: props.navigation })
      )
    );
  }

  function Announcement(props) {
    var _hidden = element.useState(false);
    var hidden = _hidden[0];
    var setHidden = _hidden[1];
    var settings = props.settings || {};
    if (!settings['banner.enabled'] || !settings['banner.text'] || hidden) return null;
    var text = settings['banner.link']
      ? h('a', { href: settings['banner.link'] }, settings['banner.text'])
      : settings['banner.text'];
    return h(
      'aside',
      {
        className: 'woact-announcement',
        role: 'status',
        style: { background: settings['banner.background'], color: settings['banner.text_color'] },
      },
      text,
      settings['banner.closable'] ? h('button', {
        type: 'button',
        onClick: function () { setHidden(true); },
        'aria-label': 'بستن اعلان',
      }, '×') : null
    );
  }

  function FeaturedImage(props) {
    if (!props.src) return null;
    return h('img', {
      className: 'woact-react-featured',
      src: props.src,
      alt: '',
      loading: 'eager',
      decoding: 'async',
    });
  }

  function SingularView(props) {
    var content = props.content || {};
    return h(
      'article',
      { className: 'woact-entry', 'data-content-id': content.id || undefined },
      h('h1', { className: 'woact-entry-title' }, content.title || ''),
      content.date ? h('time', { className: 'woact-entry-meta', dateTime: content.date }, new Date(content.date).toLocaleDateString()) : null,
      h(FeaturedImage, { src: content.featuredImage }),
      h('div', {
        className: 'woact-entry-content',
        dangerouslySetInnerHTML: { __html: content.html || '' },
      })
    );
  }

  function ArchiveView(props) {
    var content = props.content || {};
    var items = Array.isArray(content.items) ? content.items : [];
    return h(
      Fragment,
      null,
      h('h1', { className: 'woact-entry-title' }, content.title || ''),
      items.length
        ? h('div', { className: 'woact-grid' }, items.map(function (item) {
            return h(
              'article',
              { className: 'woact-card', key: item.id },
              item.featuredImage ? h('a', { href: item.url, tabIndex: -1 }, h(FeaturedImage, { src: item.featuredImage })) : null,
              h('h2', null, h('a', { href: item.url }, item.title)),
              item.excerpt ? h('p', null, item.excerpt) : null
            );
          }))
        : h('section', { className: 'woact-empty' }, h('p', null, 'محتوایی پیدا نشد.'))
    );
  }

  function NotFoundView() {
    return h(
      'section',
      { className: 'woact-empty' },
      h('h1', null, 'صفحه پیدا نشد'),
      h('p', null, 'نشانی را بررسی کنید یا به صفحهٔ اصلی بازگردید.')
    );
  }

  function App(props) {
    var data = props.data || {};
    var site = data.site || {};
    var route = data.route || {};
    var layouts = data.layouts || {};
    var Layout = window.WoactLayoutRenderer && window.WoactLayoutRenderer.Layout;
    var Page = window.WoactPageRenderer && window.WoactPageRenderer.Page;
    var dynamicData = { site: data.site || {}, post: data.content || {}, user: data.viewer || {} };
    var view = Page && data.pageDocument
      ? h(Page, { document: data.pageDocument, data: dynamicData, device: 'desktop' })
      : route.kind === 'singular'
      ? h(SingularView, { content: data.content })
      : route.kind === 'not-found'
        ? h(NotFoundView)
        : h(ArchiveView, { content: data.content });

    return h(
      Fragment,
      null,
      h(Announcement, { settings: data.settings || {} }),
      Layout && layouts.header
        ? h(Layout, { payload: layouts.header })
        : h(Header, { site: site, navigation: data.navigation || [] }),
      h('main', { id: 'woact-main', className: 'woact-main' + (Page && data.pageDocument ? ' woact-page-main' : ' woact-container') }, view),
      (data.settings || {})['footer.enabled'] === false ? null : Layout && layouts.footer
        ? h(Layout, { payload: layouts.footer })
        : h(
        'footer',
        { className: 'woact-react-footer' },
        h(
          'div',
          { className: 'woact-container' },
          h('span', null, ((data.settings || {})['footer.copyright'] || '© {year} {site_name}')
            .replace('{year}', new Date().getFullYear())
            .replace('{site_name}', site.name || 'Woact')),
          h('span', { className: 'woact-react-status' }, 'React')
        )
      )
    );
  }

  function ErrorBoundary(props) {
    element.Component.call(this, props);
    this.state = { hasError: false };
  }
  ErrorBoundary.prototype = Object.create(element.Component.prototype);
  ErrorBoundary.prototype.constructor = ErrorBoundary;
  ErrorBoundary.getDerivedStateFromError = function () {
    return { hasError: true };
  };
  ErrorBoundary.prototype.render = function () {
    if (this.state.hasError) {
      return h('div', { className: 'woact-react-error', role: 'alert' }, 'نمایش React با خطا روبه‌رو شد. صفحه را دوباره بارگذاری کنید.');
    }
    return this.props.children;
  };

  var container = document.getElementById('woact-root');
  if (!container || !bootstrap) return;

  var app = h(ErrorBoundary, null, h(App, { data: bootstrap }));
  if (typeof element.createRoot === 'function') {
    element.createRoot(container).render(app);
  } else if (typeof element.render === 'function') {
    element.render(app, container);
  }
})(window.wp, window.WoactBootstrap);

