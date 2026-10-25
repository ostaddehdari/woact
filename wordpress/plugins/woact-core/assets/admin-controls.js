(function (wp) {
  'use strict';
  if (!wp || !wp.element) return;
  var h = wp.element.createElement;
  var registry = {};
  var units = ['px', '%', 'em', 'rem', 'vw', 'vh'];

  function optionValue(option) { return typeof option === 'object' ? option.value : option; }
  function optionLabel(option) { return typeof option === 'object' ? (option.label || option.value) : option; }
  function object(value, fallback) { return value && typeof value === 'object' && !Array.isArray(value) ? value : fallback; }
  function conditionMatches(condition, values) {
    if (!condition) return true;
    if (Array.isArray(condition)) return condition.every(function (entry) { return conditionMatches(entry, values); });
    if (condition.and) return condition.and.every(function (entry) { return conditionMatches(entry, values); });
    if (condition.or) return condition.or.some(function (entry) { return conditionMatches(entry, values); });
    return Object.keys(condition).every(function (key) {
      var expected = condition[key], actual = values ? values[key] : undefined;
      if (expected && typeof expected === 'object') {
        if (Object.prototype.hasOwnProperty.call(expected, 'not')) return actual !== expected.not;
        if (Object.prototype.hasOwnProperty.call(expected, 'in')) return expected.in.indexOf(actual) !== -1;
        if (Object.prototype.hasOwnProperty.call(expected, 'gt')) return Number(actual) > Number(expected.gt);
        if (Object.prototype.hasOwnProperty.call(expected, 'lt')) return Number(actual) < Number(expected.lt);
      }
      return actual === expected;
    });
  }
  function register(type, component) { registry[type] = component; }
  function input(type, props) { return h('input', Object.assign({ type: type }, props)); }
  function select(schema, value, change, multiple) {
    return h('select', { value: value === undefined ? schema.default || (multiple ? [] : '') : value, multiple: !!multiple, onChange: function (event) { change(multiple ? Array.from(event.target.selectedOptions).map(function (item) { return item.value; }) : event.target.value); } }, (schema.options || []).map(function (option) { return h('option', { key: optionValue(option), value: optionValue(option) }, optionLabel(option)); }));
  }
  function Text(schema, value, change) { return input(schema.type === 'url' ? 'url' : 'text', { value: value || '', placeholder: schema.placeholder || '', dir: schema.dir || undefined, onChange: function (event) { change(event.target.value); } }); }
  function Textarea(schema, value, change) { return h('textarea', { rows: schema.rows || 5, value: value || '', placeholder: schema.placeholder || '', onChange: function (event) { change(event.target.value); } }); }
  function NumberControl(schema, value, change) { return h('div', { className: 'woact-control-number' }, input('number', { value: value === undefined ? schema.default || 0 : value, min: schema.min, max: schema.max, step: schema.step || 1, onChange: function (event) { change(Number(event.target.value)); } }), schema.type === 'slider' ? input('range', { value: value === undefined ? schema.default || 0 : value, min: schema.min || 0, max: schema.max || 100, step: schema.step || 1, onChange: function (event) { change(Number(event.target.value)); } }) : null); }
  function Switch(schema, value, change) { return h('button', { type: 'button', className: 'woact-control-switch ' + (value ? 'is-on' : ''), onClick: function () { change(!value); }, role: 'switch', 'aria-checked': !!value }, h('span'), h('b', null, value ? (schema.onLabel || 'روشن') : (schema.offLabel || 'خاموش'))); }
  function ButtonGroup(schema, value, change) { return h('div', { className: 'woact-control-buttons' }, (schema.options || []).map(function (option) { var key = optionValue(option); return h('button', { type: 'button', key: key, className: value === key ? 'is-active' : '', onClick: function () { change(key); } }, option.icon ? h('span', { className: 'dashicons dashicons-' + option.icon }) : null, optionLabel(option)); })); }
  function Unit(schema, value, change) { var current = object(value, { value: schema.default || 0, unit: schema.unit || 'px' }); return h('div', { className: 'woact-control-unit' }, input('number', { value: current.value, min: schema.min, max: schema.max, step: schema.step || 1, onChange: function (event) { change({ value: Number(event.target.value), unit: current.unit }); } }), h('select', { value: current.unit, onChange: function (event) { change({ value: current.value, unit: event.target.value }); } }, (schema.units || units).map(function (unit) { return h('option', { key: unit, value: unit }, unit); }))); }
  function Dimensions(schema, value, change) {
    var current = object(value, { top: 0, right: 0, bottom: 0, left: 0, unit: schema.unit || 'px', linked: true });
    function set(key, next) { var result = Object.assign({}, current); result[key] = next; if (current.linked && ['top', 'right', 'bottom', 'left'].indexOf(key) !== -1) ['top', 'right', 'bottom', 'left'].forEach(function (side) { result[side] = next; }); change(result); }
    return h('div', { className: 'woact-control-dimensions' }, h('div', null, ['top', 'right', 'bottom', 'left'].map(function (side) { return h('label', { key: side }, input('number', { value: current[side] || 0, onChange: function (event) { set(side, Number(event.target.value)); } }), h('small', null, { top: 'بالا', right: 'راست', bottom: 'پایین', left: 'چپ' }[side])); }), h('button', { type: 'button', className: current.linked ? 'is-active' : '', title: 'اتصال مقادیر', onClick: function () { set('linked', !current.linked); } }, h('span', { className: 'dashicons dashicons-admin-links' }))), h('select', { value: current.unit, onChange: function (event) { set('unit', event.target.value); } }, (schema.units || units).map(function (unit) { return h('option', { key: unit }, unit); })));
  }
  function Color(schema, value, change) { var color = typeof value === 'string' && value ? value : schema.default || '#000000'; return h('div', { className: 'woact-control-color' }, input('color', { value: /^#[0-9a-f]{6}$/i.test(color) ? color : '#000000', onChange: function (event) { change(event.target.value); } }), input('text', { value: color, dir: 'ltr', onChange: function (event) { change(event.target.value); } }), h('button', { type: 'button', title: 'پاک‌کردن', onClick: function () { change(''); } }, '×'));
  }
  function Gradient(schema, value, change) { var current = object(value, { type: 'linear', angle: 90, first: '#7c3aed', second: '#2563eb' }); return h('div', { className: 'woact-control-gradient' }, h('div', { className: 'woact-gradient-preview', style: { background: (current.type === 'radial' ? 'radial-gradient(' : 'linear-gradient(' + current.angle + 'deg,') + current.first + ',' + current.second + ')' } }), ButtonGroup({ options: [{ value: 'linear', label: 'خطی' }, { value: 'radial', label: 'شعاعی' }] }, current.type, function (next) { change(Object.assign({}, current, { type: next })); }), Color({}, current.first, function (next) { change(Object.assign({}, current, { first: next })); }), Color({}, current.second, function (next) { change(Object.assign({}, current, { second: next })); }), current.type === 'linear' ? NumberControl({ min: 0, max: 360 }, current.angle, function (next) { change(Object.assign({}, current, { angle: next })); }) : null); }
  function Typography(schema, value, change) { var current = object(value, { family: '', size: { value: 16, unit: 'px' }, weight: '400', lineHeight: { value: 1.7, unit: 'em' }, letterSpacing: { value: 0, unit: 'px' }, transform: 'none' }); function set(key, next) { change(Object.assign({}, current, (function () { var update = {}; update[key] = next; return update; })())); } return h('div', { className: 'woact-control-group woact-control-typography' }, h('label', null, 'خانواده فونت', Text({ placeholder: 'Vazirmatn' }, current.family, function (next) { set('family', next); })), h('label', null, 'اندازه', Unit({}, current.size, function (next) { set('size', next); })), h('label', null, 'وزن', select({ options: ['100', '200', '300', '400', '500', '600', '700', '800', '900'] }, current.weight, function (next) { set('weight', next); })), h('label', null, 'ارتفاع خط', Unit({ units: ['px', 'em', 'rem'] }, current.lineHeight, function (next) { set('lineHeight', next); })), h('label', null, 'فاصله حروف', Unit({ units: ['px', 'em'] }, current.letterSpacing, function (next) { set('letterSpacing', next); })), h('label', null, 'تبدیل متن', select({ options: [{ value: 'none', label: 'بدون تغییر' }, { value: 'uppercase', label: 'حروف بزرگ' }, { value: 'lowercase', label: 'حروف کوچک' }, { value: 'capitalize', label: 'ابتدای بزرگ' }] }, current.transform, function (next) { set('transform', next); })));
  }
  function Background(schema, value, change, context) { var current = object(value, { type: 'classic', color: '', image: '', gradient: null, position: 'center center', size: 'cover' }); function set(key, next) { change(Object.assign({}, current, (function () { var update = {}; update[key] = next; return update; })())); } return h('div', { className: 'woact-control-group' }, ButtonGroup({ options: [{ value: 'classic', label: 'کلاسیک' }, { value: 'gradient', label: 'گرادیان' }] }, current.type, function (next) { set('type', next); }), current.type === 'gradient' ? Gradient({}, current.gradient, function (next) { set('gradient', next); }) : h('div', null, Color({}, current.color, function (next) { set('color', next); }), Media({ button: 'انتخاب تصویر' }, current.image, function (next) { set('image', next); }, context), select({ options: ['cover', 'contain', 'auto'] }, current.size, function (next) { set('size', next); })));
  }
  function Border(schema, value, change) { var current = object(value, { type: 'none', width: { top: 0, right: 0, bottom: 0, left: 0, unit: 'px', linked: true }, color: '#000000', radius: { top: 0, right: 0, bottom: 0, left: 0, unit: 'px', linked: true }, shadow: { x: 0, y: 0, blur: 0, spread: 0, color: '#00000033', inset: false } }); function set(key, next) { change(Object.assign({}, current, (function () { var update = {}; update[key] = next; return update; })())); } return h('div', { className: 'woact-control-group' }, h('label', null, 'نوع حاشیه', select({ options: ['none', 'solid', 'double', 'dotted', 'dashed'] }, current.type, function (next) { set('type', next); })), current.type !== 'none' ? h('div', null, h('label', null, 'ضخامت', Dimensions({}, current.width, function (next) { set('width', next); })), h('label', null, 'رنگ', Color({}, current.color, function (next) { set('color', next); }))) : null, h('label', null, 'گردی گوشه', Dimensions({}, current.radius, function (next) { set('radius', next); })), h('label', null, 'سایه', Shadow({}, current.shadow, function (next) { set('shadow', next); })));
  }
  function Shadow(schema, value, change) { var current = object(value, { x: 0, y: 0, blur: 10, spread: 0, color: '#00000033', inset: false }); function set(key, next) { change(Object.assign({}, current, (function () { var update = {}; update[key] = next; return update; })())); } return h('div', { className: 'woact-control-shadow' }, ['x', 'y', 'blur', 'spread'].map(function (key) { return h('label', { key: key }, { x: 'افقی', y: 'عمودی', blur: 'محو', spread: 'گسترش' }[key], input('number', { value: current[key], onChange: function (event) { set(key, Number(event.target.value)); } })); }), Color({}, current.color, function (next) { set('color', next); }), Switch({ onLabel: 'داخلی', offLabel: 'بیرونی' }, current.inset, function (next) { set('inset', next); })); }
  function Media(schema, value, change, context) { return h('div', { className: 'woact-control-media' }, value ? h('img', { src: value, alt: '' }) : h('span', { className: 'dashicons dashicons-format-image' }), h('div', null, h('button', { type: 'button', onClick: function () { if (!wp.media) return; var frame = wp.media({ title: schema.title || 'انتخاب رسانه', multiple: false }); frame.on('select', function () { var item = frame.state().get('selection').first().toJSON(); change(item.url || ''); }); frame.open(); } }, schema.button || 'انتخاب رسانه'), value ? h('button', { type: 'button', onClick: function () { change(''); } }, 'حذف') : null)); }
  function Repeater(schema, value, change, context) { var items = Array.isArray(value) ? value : []; function update(index, key, next) { var copy = JSON.parse(JSON.stringify(items)); copy[index][key] = next; change(copy); } return h('div', { className: 'woact-control-repeater' }, items.map(function (item, index) { return h('details', { key: item._id || index, open: index === 0 }, h('summary', null, item[schema.titleField || 'title'] || 'آیتم ' + (index + 1), h('button', { type: 'button', onClick: function (event) { event.preventDefault(); var copy = items.slice(); copy.splice(index, 1); change(copy); } }, '×')), (schema.fields || []).map(function (field) { return h('label', { key: field.id }, field.label, render(field, item[field.id], function (next) { update(index, field.id, next); }, Object.assign({}, context, { values: item }))); })); }), h('button', { type: 'button', className: 'button', onClick: function () { change(items.concat([Object.assign({ _id: 'item_' + Date.now() }, schema.itemDefaults || {})])); } }, '+ افزودن آیتم'));
  }
  function render(schema, value, change, context) {
    schema = schema || {}; context = context || {};
    if (!conditionMatches(schema.condition, context.values || {})) return null;
    var component = registry[schema.type || 'text'] || Text;
    var device = context.device || 'desktop', actual = value, actualChange = change;
    if (schema.responsive) {
      var responsive = object(value, {});
      actual = responsive[device] !== undefined ? responsive[device] : (device !== 'desktop' ? responsive.desktop : schema.default);
      actualChange = function (next) { var copy = Object.assign({}, responsive); copy[device] = next; change(copy); };
    }
    return h('div', { className: 'woact-control woact-control--' + (schema.type || 'text') + (schema.responsive ? ' is-responsive' : ''), 'data-control': schema.id || '', 'data-device': device },
      schema.responsive ? h('span', { className: 'woact-control-device', title: 'مقدار واکنش‌گرا' }, h('span', { className: 'dashicons dashicons-' + (device === 'mobile' ? 'smartphone' : device) })) : null,
      component(schema, actual, actualChange, context),
      schema.resettable === false ? null : h('button', { type: 'button', className: 'woact-control-reset', title: 'بازنشانی', onClick: function () { actualChange(schema.default === undefined ? '' : schema.default); } }, h('span', { className: 'dashicons dashicons-image-rotate' }))
    );
  }

  register('text', Text); register('url', Text); register('icon', Text); register('code', Textarea);
  register('textarea', Textarea); register('number', NumberControl); register('slider', NumberControl);
  register('select', function (s, v, c) { return select(s, v, c, false); }); register('multiselect', function (s, v, c) { return select(s, v, c, true); });
  register('checkbox', Switch); register('switch', Switch); register('radio', ButtonGroup); register('button_group', ButtonGroup);
  register('unit', Unit); register('dimensions', Dimensions); register('color', Color); register('gradient', Gradient); register('typography', Typography);
  register('background', Background); register('border', Border); register('shadow', Shadow); register('media', Media); register('repeater', Repeater); register('nested_repeater', Repeater);
  window.WoactControls = { version: 1, register: register, render: render, conditionMatches: conditionMatches, types: function () { return Object.keys(registry); } };
})(window.wp);
