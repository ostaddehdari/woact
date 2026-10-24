(function (wp) {
  'use strict';
  if (!wp || !wp.element) return;
  var h = wp.element.createElement;
  var useState = wp.element.useState;

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
  function lines(value) { return String(value || '').split(/\r?\n/).filter(Boolean).map(function (line) { var p = line.split('|'); return { a: p[0] || '', b: p[1] || '', c: p[2] || '', d: p[3] || '' }; }); }
  function Cards(props) { return h('div', { className: 'woact-widget-cards', style: { '--woact-widget-columns': props.columns || 3 } }, (props.items || []).map(function (item, index) { return h('article', { className: 'woact-widget-card', key: item.id || index }, item.image ? h('a', { href: item.url || '#', tabIndex: -1 }, h('img', { src: item.image, alt: '', loading: 'lazy' })) : null, h('h3', null, item.url ? h('a', { href: item.url }, item.title || item.name) : item.title || item.name), item.priceHtml ? h('div', { className: 'woact-widget-price', dangerouslySetInnerHTML: { __html: item.priceHtml } }) : null, item.excerpt ? h('p', null, item.excerpt) : null); })); }
  function Node(props) {
    var node = props.node || {};
    var p = node.props || {};
    var device = props.device || 'desktop';
    var _active = useState(0), active = _active[0], setActive = _active[1];
    if (!visible(node, device)) return null;
    var dynamic = get(node.dynamic, props.data);
    var style = resolvedStyle(node, device);
    var common = { className: 'woact-page-node woact-page-node--' + node.type, style: style, 'data-woact-node': node.id };
    var widgets = (props.data && props.data.widgets) || {};
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
    if (node.type === 'badge') return h('span', common, p.text || 'جدید');
    if (node.type === 'image_box') return h('article', common, p.url ? h('img', { src: p.url, alt: p.title || '', loading: 'lazy' }) : null, h('h3', null, p.title || ''), h('p', null, p.text || ''));
    if (node.type === 'icon_box') return h('article', common, h('span', { className: 'woact-widget-icon' }, p.icon || '★'), h('h3', null, p.title || ''), h('p', null, p.text || ''));
    if (node.type === 'gallery') return h('div', Object.assign({}, common, { className: common.className + ' woact-widget-gallery', style: Object.assign({}, style, { '--woact-widget-columns': p.columns || 3 }) }), lines(p.images).map(function (item, index) { return h('img', { key: index, src: item.a, alt: item.b || '', loading: 'lazy' }); }));
    if (node.type === 'video') return p.url ? h('video', Object.assign({}, common, { src: p.url, poster: p.poster || undefined, controls: true, preload: 'metadata' })) : h('div', common, 'نشانی ویدئو را وارد کنید.');
    if (node.type === 'audio') return p.url ? h('audio', Object.assign({}, common, { src: p.url, controls: true, preload: 'metadata' })) : h('div', common, 'نشانی صوت را وارد کنید.');
    if (node.type === 'carousel') { var slides = lines(p.items); var count = Math.max(slides.length, 1); var slide = slides[active % count] || {}; return h('div', common, slide.b ? h('img', { src: slide.b, alt: slide.a }) : null, h('strong', null, slide.a), h('div', { className: 'woact-widget-nav' }, h('button', { type: 'button', onClick: function () { setActive((active - 1 + count) % count); } }, '‹'), h('button', { type: 'button', onClick: function () { setActive((active + 1) % count); } }, '›'))); }
    if (node.type === 'tabs') { var tabs = lines(p.items); return h('div', common, h('div', { className: 'woact-widget-tabs' }, tabs.map(function (item, index) { return h('button', { key: index, type: 'button', className: active === index ? 'is-active' : '', onClick: function () { setActive(index); } }, item.a); })), h('div', { className: 'woact-widget-tab-panel' }, tabs[active] ? tabs[active].b : '')); }
    if (node.type === 'accordion' || node.type === 'faq') return h('div', common, lines(p.items).map(function (item, index) { return h('details', { key: index, open: index === 0 }, h('summary', null, item.a), h('div', null, item.b)); }));
    if (node.type === 'countdown') return h('div', common, h('span', null, p.label || ''), h('strong', null, p.date ? new Date(p.date).toLocaleString() : 'زمان را تعیین کنید'));
    if (node.type === 'counter') return h('div', common, h('strong', null, String(p.value || 0) + (p.suffix || '')), h('span', null, p.label || ''));
    if (node.type === 'progress') return h('div', common, h('label', null, p.label || ''), h('progress', { max: 100, value: Math.max(0, Math.min(100, Number(p.value || 0))) }), h('span', null, (p.value || 0) + '%'));
    if (node.type === 'hero') return h('section', Object.assign({}, common, { className: common.className + ' woact-widget-hero' }), h('div', null, h('small', null, p.eyebrow || ''), h('h1', null, p.title || ''), h('p', null, p.text || ''), h('a', { className: 'woact-widget-button', href: p.buttonUrl || '#' }, p.buttonLabel || '')), p.image ? h('img', { src: p.image, alt: '', loading: 'eager' }) : null);
    if (['services', 'team', 'client_logos', 'portfolio', 'timeline'].indexOf(node.type) !== -1) return h('div', Object.assign({}, common, { className: common.className + ' woact-widget-cards' }), lines(p.items).map(function (item, index) { return h('article', { className: 'woact-widget-card', key: index }, item.c ? h('img', { src: item.c, alt: item.a }) : null, h('h3', null, item.a), h('p', null, item.b)); }));
    if (node.type === 'testimonial') return h('blockquote', common, h('p', null, p.text || ''), h('footer', null, h('strong', null, p.name || ''), p.role ? ' — ' + p.role : ''));
    if (node.type === 'pricing') return h('article', Object.assign({}, common, { className: common.className + ' woact-widget-pricing' }), h('h3', null, p.title || ''), h('strong', { className: 'woact-widget-price' }, p.price || ''), h('ul', null, lines(p.features).map(function (item, index) { return h('li', { key: index }, item.a); })), h('button', { type: 'button' }, p.buttonLabel || 'انتخاب'));
    if (node.type === 'contact_cta') return h('section', Object.assign({}, common, { className: common.className + ' woact-widget-cta' }), h('div', null, h('h2', null, p.title || ''), h('p', null, p.text || '')), h('a', { className: 'woact-widget-button', href: p.buttonUrl || '#' }, p.buttonLabel || 'تماس'));
    if (node.type === 'map') return p.embedUrl ? h('iframe', Object.assign({}, common, { src: p.embedUrl, title: p.title || 'نقشه', loading: 'lazy', referrerPolicy: 'no-referrer-when-downgrade' })) : h('div', common, 'نشانی Embed نقشه را وارد کنید.');
    if (node.type === 'social_profiles') return h('div', common, lines(p.items).map(function (item, index) { return h('a', { key: index, href: item.b || '#', rel: 'noopener noreferrer' }, item.a); }));
    if (node.type === 'post_grid' || node.type === 'post_list') return h(Cards, { items: (widgets.posts || []).slice(0, p.count || 6), columns: node.type === 'post_list' ? 1 : p.columns || 3 });
    if (node.type === 'categories' || node.type === 'tags') { var terms = ((widgets.taxonomies || {})[node.type] || []); return h('div', common, terms.map(function (term) { return h('a', { key: term.id, href: term.url }, term.name); })); }
    if (node.type === 'post_title') return h('h1', common, ((widgets.wordpress || {}).post || {}).title || (props.data.post || {}).title || 'عنوان نوشته');
    if (node.type === 'post_content' || node.type === 'single_post') return h('article', Object.assign({}, common, { dangerouslySetInnerHTML: { __html: (((widgets.wordpress || {}).post || {}).content || '') } }));
    if (node.type === 'post_image') { var postImage = ((widgets.wordpress || {}).post || {}).image || (props.data.post || {}).featuredImage; return postImage ? h('img', Object.assign({}, common, { src: postImage, alt: '' })) : h('div', common, 'تصویر شاخص'); }
    if (node.type === 'author') return h('div', common, ((widgets.wordpress || {}).post || {}).author || (props.data.user || {}).name || 'نویسنده');
    if (node.type === 'comments') return h('div', common, ((widgets.wordpress || {}).comments || []).map(function (comment) { return h('article', { key: comment.id }, h('strong', null, comment.author), h('p', null, comment.content)); }));
    if (node.type === 'search_widget') return h('form', Object.assign({}, common, { role: 'search', action: (props.data.site || {}).url || '/' }), h('input', { type: 'search', name: 's', placeholder: 'جست‌وجو…' }), h('button', { type: 'submit' }, 'جست‌وجو'));
    if (node.type === 'breadcrumb_widget') return h('nav', common, h('a', { href: (props.data.site || {}).url || '/' }, 'خانه'), ' / ', (props.data.post || {}).title || 'صفحه');
    if (node.type === 'menu_widget') return h('nav', common, 'منوی ' + (p.location || 'primary'));
    if (node.type === 'login_widget') { var account = widgets.account || {}; return h('a', Object.assign({}, common, { href: account.loggedIn ? account.accountUrl : account.loginUrl }), account.loggedIn ? 'حساب من' : 'ورود'); }
    if (node.type === 'user_profile') return h('div', common, (widgets.account || {}).name || 'کاربر مهمان');
    if (node.type === 'shortcode') return h('div', Object.assign({}, common, { dangerouslySetInnerHTML: { __html: ((widgets.wordpress || {}).shortcodes || {})[node.id] || 'Shortcode در خروجی صفحه پردازش می‌شود.' } }));
    if (node.type === 'lead_form') return h('form', common, h('h3', null, p.title || ''), lines(p.fields).map(function (item, index) { return h('label', { key: index }, h('span', null, item.b), item.c === 'textarea' ? h('textarea', { name: item.a, required: item.d === 'required' }) : h('input', { name: item.a, type: item.c || 'text', required: item.d === 'required' })); }), h('button', { type: 'submit' }, p.buttonLabel || 'ارسال'));
    if (['product_grid', 'sale_products', 'featured_products', 'recent_products', 'related_products', 'upsell_products'].indexOf(node.type) !== -1) return h(Cards, { items: (widgets.commerce || {}).products || [], columns: p.columns || 4 });
    if (node.type === 'product_categories') return h('div', common, ((widgets.commerce || {}).categories || []).map(function (term) { return h('a', { key: term.id, href: term.url }, term.name); }));
    if (node.type === 'product_filters') return h('form', common, h('input', { type: 'number', placeholder: 'حداقل قیمت' }), h('input', { type: 'number', placeholder: 'حداکثر قیمت' }), h('button', { type: 'submit' }, 'اعمال فیلتر'));
    if (node.type === 'product_sort') return h('select', common, h('option', null, 'مرتب‌سازی پیش‌فرض'), h('option', null, 'جدیدترین'), h('option', null, 'ارزان‌ترین'));
    if (node.type === 'product_results_count') return h('span', common, ((widgets.commerce || {}).products || []).length + ' محصول');
    if (node.type === 'product_pagination') return h('nav', common, h('a', { href: '#' }, '۱'), h('a', { href: '#' }, '۲'));
    var product = widgets.product || {};
    if (node.type === 'product_title') return h('h1', common, product.title || 'عنوان محصول');
    if (node.type === 'product_gallery') return h('div', Object.assign({}, common, { className: common.className + ' woact-widget-gallery' }), [product.image].concat(product.gallery || []).filter(Boolean).map(function (src, index) { return h('img', { src: src, alt: '', key: index }); }));
    if (node.type === 'product_price') return h('div', Object.assign({}, common, { dangerouslySetInnerHTML: { __html: product.priceHtml || 'قیمت محصول' } }));
    if (node.type === 'product_rating') return h('div', common, '★ ' + (product.rating || 0));
    if (node.type === 'product_stock') return h('div', common, product.stock === 'instock' ? 'موجود' : 'ناموجود');
    if (node.type === 'add_to_cart' || node.type === 'buy_now') return h('a', Object.assign({}, common, { className: common.className + ' woact-widget-button', href: product.addToCartUrl || '#' }), node.type === 'buy_now' ? 'خرید سریع' : 'افزودن به سبد');
    if (['product_variations', 'product_meta', 'product_tabs', 'product_reviews', 'delivery_estimate'].indexOf(node.type) !== -1) return h('div', common, { product_variations: 'انتخاب متغیر محصول', product_meta: product.sku ? 'SKU: ' + product.sku : 'مشخصات محصول', product_tabs: 'توضیحات و مشخصات', product_reviews: 'نظرات خریداران', delivery_estimate: 'زمان تقریبی تحویل' }[node.type]);
    var accountData = widgets.account || {}, commerce = widgets.commerce || {};
    if (node.type === 'mini_cart') return h('a', Object.assign({}, common, { href: commerce.cartUrl || '#' }), 'سبد خرید (' + (commerce.cartCount || 0) + ')');
    if (node.type === 'cart_page') return h('section', common, h('h2', null, 'سبد خرید'), h('a', { href: commerce.checkoutUrl || '#' }, 'ادامه و تسویه حساب'));
    if (node.type === 'checkout' || node.type === 'order_review') return h('section', common, h('h2', null, node.type === 'checkout' ? 'تسویه حساب امن' : 'مرور سفارش'), h('p', null, 'این بخش در صفحه واقعی به جریان امن WooCommerce متصل می‌شود.'));
    if (node.type === 'coupon') return h('form', common, h('input', { placeholder: 'کد تخفیف' }), h('button', { type: 'submit' }, 'اعمال'));
    if (node.type === 'account_nav') return h('nav', common, h('a', { href: accountData.accountUrl || '#' }, 'پیشخوان'), h('a', { href: accountData.ordersUrl || '#' }, 'سفارش‌ها'), h('a', { href: accountData.downloadsUrl || '#' }, 'دانلودها'));
    if (node.type === 'orders' || node.type === 'downloads' || node.type === 'addresses') return h('a', Object.assign({}, common, { href: accountData[node.type + 'Url'] || accountData.accountUrl || '#' }), { orders: 'سفارش‌های من', downloads: 'دانلودهای من', addresses: 'نشانی‌های من' }[node.type]);
    if (['thank_you', 'wishlist_widget', 'compare_widget', 'waitlist'].indexOf(node.type) !== -1) return h('section', common, { thank_you: 'از خرید شما سپاسگزاریم.', wishlist_widget: 'فهرست علاقه‌مندی‌ها', compare_widget: 'مقایسه محصولات', waitlist: 'اطلاع‌رسانی موجودشدن محصول' }[node.type]);
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
