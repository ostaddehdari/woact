(function (wp) {
  'use strict';
  if (!wp || !wp.element) return;
  var h = wp.element.createElement;

  var groups = {
    style: [
      { id: 'globalTokens', label: 'توکن‌های سراسری', icon: 'admin-site-alt3', fields: [{ id: 'colorToken', label: 'رنگ متن سراسری', type: 'select', tokenSource: 'colors' }, { id: 'backgroundToken', label: 'رنگ پس‌زمینه سراسری', type: 'select', tokenSource: 'colors' }, { id: 'typographyToken', label: 'تایپوگرافی سراسری', type: 'select', tokenSource: 'typography' }] },
      { id: 'typography', label: 'تایپوگرافی', icon: 'editor-textcolor', fields: [{ id: 'typography', label: 'تایپوگرافی', type: 'typography' }, { id: 'color', label: 'رنگ متن', type: 'color' }] },
      { id: 'background', label: 'پس‌زمینه', icon: 'art', fields: [{ id: 'backgroundControl', label: 'نوع و تصویر پس‌زمینه', type: 'background' }] },
      { id: 'border', label: 'حاشیه و سایه', icon: 'admin-customizer', fields: [{ id: 'borderControl', label: 'حاشیه، گردی و سایه', type: 'border' }] },
    ],
    advanced: [
      { id: 'layout', label: 'طرح‌بندی', icon: 'align-wide', fields: [
        { id: 'globalStyle', label: 'استایل ذخیره‌شده', type: 'select', tokenSource: 'styles' },
        { id: 'marginBox', label: 'فاصله بیرونی', type: 'dimensions' }, { id: 'paddingBox', label: 'فاصله داخلی', type: 'dimensions' },
        { id: 'widthUnit', label: 'عرض', type: 'unit', units: ['px', '%', 'vw'] }, { id: 'minHeightUnit', label: 'حداقل ارتفاع', type: 'unit', units: ['px', 'vh'] },
        { id: 'position', label: 'موقعیت', type: 'select', options: [{ value: '', label: 'پیش‌فرض' }, 'relative', 'absolute', 'fixed', 'sticky'] },
        { id: 'zIndex', label: 'Z-index', type: 'number', min: -9999, max: 9999 }
      ] },
      { id: 'motion', label: 'موشن افکت', icon: 'controls-play', fields: [
        { id: 'entranceAnimation', label: 'انیمیشن ورود', type: 'select', options: [{ value: '', label: 'بدون انیمیشن' }, 'fadeIn', 'fadeInUp', 'fadeInDown', 'slideInRight', 'slideInLeft', 'zoomIn'] },
        { id: 'animationDuration', label: 'مدت اجرا (ms)', type: 'slider', min: 100, max: 5000, step: 50, default: 600 },
        { id: 'animationDelay', label: 'تأخیر (ms)', type: 'slider', min: 0, max: 5000, step: 50, default: 0 },
        { id: 'hoverAnimation', label: 'حرکت Hover', type: 'select', options: [{ value: '', label: 'بدون حرکت' }, 'grow', 'shrink', 'float', 'pulse', 'rotate'] }
      ] },
      { id: 'transform', label: 'تبدیل', icon: 'image-rotate', fields: [
        { id: 'translateX', label: 'جابجایی افقی', type: 'unit', units: ['px', '%'] }, { id: 'translateY', label: 'جابجایی عمودی', type: 'unit', units: ['px', '%'] },
        { id: 'scale', label: 'مقیاس', type: 'slider', min: 0, max: 3, step: 0.01, default: 1 }, { id: 'rotate', label: 'چرخش', type: 'slider', min: -360, max: 360, default: 0 },
        { id: 'skewX', label: 'کجی افقی', type: 'slider', min: -90, max: 90, default: 0 }, { id: 'skewY', label: 'کجی عمودی', type: 'slider', min: -90, max: 90, default: 0 }
      ] },
      { id: 'mask', label: 'ماسک', icon: 'format-image', fields: [
        { id: 'maskShape', label: 'شکل ماسک', type: 'select', options: [{ value: '', label: 'بدون ماسک' }, 'circle', 'ellipse', 'triangle', 'diamond', 'hexagon', 'blob'] },
        { id: 'maskSize', label: 'اندازه ماسک', type: 'select', options: ['cover', 'contain'] }, { id: 'maskPosition', label: 'موقعیت', type: 'select', options: ['center', 'top', 'bottom', 'left', 'right'] }
      ] },
      { id: 'responsive', label: 'واکنش‌گرا', icon: 'smartphone', custom: 'responsive' },
      { id: 'attributes', label: 'ویژگی‌ها', icon: 'editor-code', fields: [
        { id: 'cssId', label: 'شناسه CSS', type: 'text', placeholder: 'my-element' }, { id: 'cssClasses', label: 'کلاس‌های CSS', type: 'text', placeholder: 'class-one class-two' },
        { id: 'attributes', label: 'ویژگی‌های HTML', type: 'repeater', titleField: 'key', itemDefaults: { key: '', value: '' }, fields: [{ id: 'key', label: 'نام ویژگی', type: 'text' }, { id: 'value', label: 'مقدار', type: 'text' }] }
      ] },
      { id: 'customCss', label: 'CSS سفارشی', icon: 'editor-code', fields: [{ id: 'customCss', label: 'از selector برای اشاره به همین المان استفاده کنید', type: 'code', rows: 9, placeholder: 'selector { color: red; }' }] }
    ]
  };

  function defaults() { return { cssId: '', cssClasses: '', attributes: [], customCss: '', entranceAnimation: '', animationDuration: 600, animationDelay: 0, hoverAnimation: '', maskShape: '', maskSize: 'cover', maskPosition: 'center' }; }
  function Accordion(props) {
    var body = props.group.custom === 'responsive' ? props.responsive() : (props.group.fields || []).map(function (field) {
      var schema = Object.assign({}, field);
      if (field.tokenSource) schema.options = [{ value: '', label: 'بدون اتصال' }].concat((((props.globals || {})[field.tokenSource]) || []).map(function (item) { return { value: item.id, label: item.name || item.id }; }));
      return h('label', { className: 'woact-design-field', key: field.id }, h('span', null, field.label), window.WoactControls.render(schema, props.values[field.id], function (value) { props.change(field.id, value); }, { values: props.values, device: props.device }));
    });
    return h('details', { className: 'woact-design-section', open: props.index === 0 }, h('summary', null, h('span', { className: 'dashicons dashicons-' + props.group.icon }), h('strong', null, props.group.label), h('span', { className: 'dashicons dashicons-arrow-down-alt2' })), h('div', { className: 'woact-design-section__body' }, body));
  }
  function Panel(props) { return h('div', { className: 'woact-design-panel' }, (groups[props.tab] || []).map(function (group, index) { return h(Accordion, Object.assign({ key: group.id, group: group, index: index }, props)); })); }

  window.WoactDesignSystem = { version: 1, groups: groups, defaults: defaults, Panel: Panel };
})(window.wp);
