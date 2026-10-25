(function (wp) {
  'use strict';
  if (!wp || !wp.element) return;
  var h = wp.element.createElement;
  function id(prefix) { return prefix + '_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6); }
  function defaults() {
    return {
      colors: [{ id: 'primary', name: 'اصلی', value: '#7c3aed' }, { id: 'text', name: 'متن', value: '#17202a' }, { id: 'surface', name: 'سطح', value: '#ffffff' }],
      typography: [{ id: 'body', name: 'متن اصلی', family: 'Vazirmatn', size: 16, weight: 400, lineHeight: 1.8 }, { id: 'heading', name: 'عنوان', family: 'Vazirmatn', size: 36, weight: 800, lineHeight: 1.3 }],
      spacing: [{ id: 'xs', name: 'خیلی کم', value: 4 }, { id: 'sm', name: 'کم', value: 8 }, { id: 'md', name: 'متوسط', value: 16 }, { id: 'lg', name: 'زیاد', value: 32 }],
      radii: [{ id: 'sm', name: 'کم', value: 6 }, { id: 'md', name: 'متوسط', value: 12 }, { id: 'pill', name: 'کپسولی', value: 999 }],
      shadows: [{ id: 'card', name: 'کارت', value: '0 10px 30px #0f172a1a' }],
      styles: [{ id: 'card', name: 'کارت عمومی', colorToken: 'text', backgroundToken: 'surface', paddingToken: 'md', radiusToken: 'md', shadowToken: 'card', className: 'woact-global-card' }],
      breakpoints: { tablet: 1024, mobile: 767 }, customCss: ''
    };
  }
  var schemas = {
    colors: { label: 'رنگ‌های سراسری', icon: 'art', type: 'repeater', titleField: 'name', itemDefaults: { id: '', name: '', value: '#000000' }, fields: [{ id: 'id', label: 'کلید', type: 'text' }, { id: 'name', label: 'نام', type: 'text' }, { id: 'value', label: 'رنگ', type: 'color' }] },
    typography: { label: 'تایپوگرافی سراسری', icon: 'editor-textcolor', type: 'repeater', titleField: 'name', itemDefaults: { id: '', name: '', family: 'Vazirmatn', size: 16, weight: 400, lineHeight: 1.8 }, fields: [{ id: 'id', label: 'کلید', type: 'text' }, { id: 'name', label: 'نام', type: 'text' }, { id: 'family', label: 'فونت', type: 'text' }, { id: 'size', label: 'اندازه', type: 'number' }, { id: 'weight', label: 'وزن', type: 'number' }, { id: 'lineHeight', label: 'ارتفاع خط', type: 'number', step: .1 }] },
    spacing: { label: 'فاصله‌های سراسری', icon: 'image-flip-horizontal', type: 'repeater', titleField: 'name', itemDefaults: { id: '', name: '', value: 16 }, fields: [{ id: 'id', label: 'کلید', type: 'text' }, { id: 'name', label: 'نام', type: 'text' }, { id: 'value', label: 'پیکسل', type: 'number' }] },
    radii: { label: 'گردی‌های سراسری', icon: 'marker', type: 'repeater', titleField: 'name', itemDefaults: { id: '', name: '', value: 12 }, fields: [{ id: 'id', label: 'کلید', type: 'text' }, { id: 'name', label: 'نام', type: 'text' }, { id: 'value', label: 'پیکسل', type: 'number' }] },
    shadows: { label: 'سایه‌های سراسری', icon: 'admin-appearance', type: 'repeater', titleField: 'name', itemDefaults: { id: '', name: '', value: '' }, fields: [{ id: 'id', label: 'کلید', type: 'text' }, { id: 'name', label: 'نام', type: 'text' }, { id: 'value', label: 'مقدار CSS', type: 'text', dir: 'ltr' }] },
    styles: { label: 'کلاس‌ها و استایل‌های ذخیره‌شده', icon: 'screenoptions', type: 'repeater', titleField: 'name', itemDefaults: { id: '', name: '', colorToken: '', backgroundToken: '', paddingToken: '', radiusToken: '', shadowToken: '', className: '' }, fields: [{ id: 'id', label: 'کلید', type: 'text' }, { id: 'name', label: 'نام', type: 'text' }, { id: 'colorToken', label: 'کلید رنگ متن', type: 'text' }, { id: 'backgroundToken', label: 'کلید پس‌زمینه', type: 'text' }, { id: 'paddingToken', label: 'کلید فاصله', type: 'text' }, { id: 'radiusToken', label: 'کلید گردی', type: 'text' }, { id: 'shadowToken', label: 'کلید سایه', type: 'text' }, { id: 'className', label: 'کلاس CSS', type: 'text' }] }
  };
  function normalize(value) { return Object.assign(defaults(), value || {}); }
  function GlobalPanel(props) {
    var value = normalize(props.value);
    function change(key, next) { var copy = Object.assign({}, value); copy[key] = next; props.change(copy); }
    function field(key) { var schema = schemas[key]; return h('details', { className: 'woact-global-section', key: key, open: key === 'colors' }, h('summary', null, h('span', { className: 'dashicons dashicons-' + schema.icon }), h('strong', null, schema.label)), h('div', null, window.WoactControls.render(schema, value[key], function (next) { change(key, next.map(function (item) { return Object.assign({}, item, { id: item.id || id(key.slice(0, 2)) }); })); }, { values: value }))); }
    return h('div', { className: 'woact-global-design' }, h('header', null, h('span', { className: 'dashicons dashicons-admin-site-alt3' }), h('div', null, h('strong', null, 'طراحی سراسری'), h('small', null, 'توکن‌های مشترک تمام ویجت‌ها'))), ['colors', 'typography', 'spacing', 'radii', 'shadows', 'styles'].map(field), h('details', { className: 'woact-global-section' }, h('summary', null, h('span', { className: 'dashicons dashicons-smartphone' }), h('strong', null, 'Breakpointها')), h('div', { className: 'woact-global-breakpoints' }, h('label', null, 'تبلت', h('input', { type: 'number', value: value.breakpoints.tablet, onChange: function (event) { change('breakpoints', Object.assign({}, value.breakpoints, { tablet: Number(event.target.value) })); } })), h('label', null, 'موبایل', h('input', { type: 'number', value: value.breakpoints.mobile, onChange: function (event) { change('breakpoints', Object.assign({}, value.breakpoints, { mobile: Number(event.target.value) })); } })))), h('details', { className: 'woact-global-section' }, h('summary', null, h('span', { className: 'dashicons dashicons-editor-code' }), h('strong', null, 'CSS سراسری صفحه')), h('div', null, window.WoactControls.render({ id: 'customCss', type: 'code', rows: 10 }, value.customCss, function (next) { change('customCss', next); }, { values: value }))));
  }
  window.WoactGlobalDesign = { version: 1, defaults: defaults, normalize: normalize, Panel: GlobalPanel };
})(window.wp);
