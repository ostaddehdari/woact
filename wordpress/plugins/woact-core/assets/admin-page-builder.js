(function (wp, config) {
  'use strict';
  if (!wp || !wp.element || !wp.apiFetch) return;
  var e = wp.element, h = e.createElement, Fragment = e.Fragment;
  var useState = e.useState, useEffect = e.useEffect, useRef = e.useRef;
  var apiFetch = wp.apiFetch;
  function clone(v) { return JSON.parse(JSON.stringify(v)); }
  function same(a, b) { return JSON.stringify(a) === JSON.stringify(b); }
  function uid(type) { return type + '_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7); }
  function visibility() { return { desktop: true, tablet: true, mobile: true }; }
  function definition(manifest, type) { return (manifest.components || []).find(function (item) { return item.type === type; }); }
  function walk(nodes, id, parent) { for (var i = 0; i < nodes.length; i++) { if (nodes[i].id === id) return { node: nodes[i], parent: parent, list: nodes, index: i }; var found = walk(nodes[i].children || [], id, nodes[i]); if (found) return found; } return null; }
  function contains(node, id) { if (!node) return false; if (node.id === id) return true; return (node.children || []).some(function (child) { return contains(child, id); }); }
  function renewIds(node) { node.id = uid(node.type); (node.children || []).forEach(renewIds); return node; }
  function canContain(node) { return ['flex_container', 'grid_container', 'section', 'container', 'columns', 'column'].indexOf(node && node.type) !== -1; }
  function makeNode(manifest, type) { var def = definition(manifest, type) || {}, grid = type === 'grid_container'; return { id: uid(type), type: type, props: clone(def.defaults || {}), style: { display: grid ? 'grid' : (canContain({ type: type }) ? 'flex' : 'block'), direction: type === 'columns' || type === 'flex_container' ? 'row' : 'column', wrap: 'nowrap', gap: type === 'columns' ? 20 : 16, columnGap: 16, rowGap: 16, gridColumns: grid ? 2 : 0, gridRows: 0, padding: type === 'section' || type === 'flex_container' || grid ? 24 : 0, margin: 0, background: '', color: '', width: 0, maxWidth: type === 'section' ? 1180 : 0, minHeight: type === 'section' || type === 'flex_container' || grid ? 120 : 0, align: 'stretch', justify: 'start', justifyItems: 'stretch', alignItems: 'stretch', fontSize: type === 'heading' ? 36 : 0, fontWeight: type === 'heading' ? 700 : 0, radius: 0, borderWidth: 0, borderColor: '', shadow: '', zIndex: 0 }, responsive: { tablet: {}, mobile: {} }, design: window.WoactDesignSystem ? window.WoactDesignSystem.defaults() : {}, visibility: visibility(), dynamic: '', locked: false, children: [] }; }

  function Notice(props) { return props.value ? h('div', { className: 'woact-page-notice is-' + props.value.type }, props.value.message, h('button', { onClick: props.close }, '×')) : null; }
  function Devices(props) { return h('div', { className: 'woact-page-devices', role: 'tablist', 'aria-label': 'پیش‌نمایش واکنش‌گرا' }, [['desktop', 'desktop', 'دسکتاپ'], ['tablet', 'tablet', 'تبلت'], ['mobile', 'smartphone', 'موبایل']].map(function (item) { return h('button', { key: item[0], title: item[2], 'aria-label': item[2], className: props.value === item[0] ? 'is-active' : '', onClick: function () { props.change(item[0]); } }, h('span', { className: 'dashicons dashicons-' + item[1] }), h('span', { className: 'woact-device-label' }, item[2])); })); }

  function Palette(props) {
    var _tab = useState('elements'), tab = _tab[0], setTab = _tab[1];
    var _query = useState(''), query = _query[0], setQuery = _query[1];
    function nav(nodes, depth) { return nodes.map(function (node) { var def = definition(props.manifest, node.type); return h('div', { key: node.id, className: 'woact-page-nav-item', style: { '--depth': depth } }, h('button', { onClick: function () { props.select(node.id); } }, h('span', { className: 'dashicons dashicons-' + (def ? def.icon : 'admin-generic') }), def ? def.label : node.type), node.children && node.children.length ? nav(node.children, depth + 1) : null); }); }
    return h('div', { className: 'woact-page-palette' }, h('div', { className: 'woact-page-tabs' }, h('button', { className: tab === 'elements' ? 'is-active' : '', onClick: function () { setTab('elements'); } }, 'ابزارک‌ها'), h('button', { className: tab === 'global' ? 'is-active' : '', onClick: function () { setTab('global'); } }, 'سراسری‌ها')),
      tab === 'global' && window.WoactGlobalDesign ? h(window.WoactGlobalDesign.Panel, { value: props.document.settings.designSystem, change: props.changeGlobal }) : h(Fragment, null,
        h('div', { className: 'woact-page-search' }, h('span', { className: 'dashicons dashicons-search' }), h('input', { value: query, placeholder: 'جست‌وجوی المان…', onChange: function (event) { setQuery(event.target.value); } })),
        (props.manifest.categories || []).map(function (cat) { var items = (props.manifest.components || []).filter(function (item) { return !item.hidden && item.category === cat.id && (!query || item.label.indexOf(query) !== -1 || item.type.indexOf(query.toLowerCase()) !== -1); }); return items.length ? h('section', { key: cat.id }, h('h3', null, cat.label), h('div', { className: 'woact-page-elements' }, items.map(function (item) { return h('button', { key: item.type, draggable: true, onDragStart: function (event) { event.dataTransfer.setData('text/woact-page', JSON.stringify({ action: 'new', type: item.type })); }, onClick: function () { item.container ? props.structure(item.type) : props.add(item.type); } }, h('span', { className: 'dashicons dashicons-' + item.icon }), item.label); }))) : null; })
      )
    );
  }

  function Navigator(props) {
    var _collapsed = useState({}), collapsed = _collapsed[0], setCollapsed = _collapsed[1];
    var _query = useState(''), query = _query[0], setQuery = _query[1];
    function matches(node) { var def = definition(props.manifest, node.type) || { label: node.type }; return !query || def.label.indexOf(query) !== -1 || node.type.indexOf(query.toLowerCase()) !== -1 || (node.children || []).some(matches); }
    function branch(nodes, depth) {
      return (nodes || []).filter(matches).map(function (node) {
        var def = definition(props.manifest, node.type) || { label: node.type, icon: 'admin-generic' };
        return h('div', { className: 'woact-tree-branch', key: node.id },
          h('button', { className: 'woact-tree-item ' + (props.selected === node.id ? 'is-selected' : ''), style: { '--depth': depth }, draggable: !node.locked, onDragStart: function (event) { event.dataTransfer.setData('text/woact-page', JSON.stringify({ action: 'move', id: node.id })); }, onClick: function () { props.select(node.id); }, onContextMenu: function (event) { props.context(node.id, event); } },
            canContain(node) ? h('span', { className: 'dashicons dashicons-arrow-' + (collapsed[node.id] ? 'left' : 'down') + '-alt2', onClick: function (event) { event.stopPropagation(); setCollapsed(Object.assign({}, collapsed, (function () { var x = {}; x[node.id] = !collapsed[node.id]; return x; })())); } }) : h('span', { className: 'woact-tree-leaf' }), h('span', { className: 'dashicons dashicons-' + def.icon }), h('span', { className: 'woact-tree-title' }, def.label), node.locked ? h('span', { className: 'dashicons dashicons-lock' }) : null, node.visibility && node.visibility[props.device] === false ? h('span', { className: 'dashicons dashicons-hidden' }) : null),
          !collapsed[node.id] ? branch(node.children, depth + 1) : null
        );
      });
    }
    return h('aside', { className: 'woact-navigator-float' }, h('header', null, h('strong', null, 'ساختار صفحه'), h('button', { onClick: props.close, 'aria-label': 'بستن' }, '×')), h('div', { className: 'woact-navigator-search' }, h('span', { className: 'dashicons dashicons-search' }), h('input', { value: query, onChange: function (event) { setQuery(event.target.value); }, placeholder: 'جست‌وجوی ساختار…' })), h('div', { className: 'woact-tree' }, branch(props.document.nodes, 0), !(props.document.nodes || []).length ? h('p', null, 'هنوز المانی در صفحه وجود ندارد.') : null));
  }

  function ContextMenu(props) {
    if (!props.value) return null;
    var action = function (name) { return function () { props.run(name, props.value.id); props.close(); }; };
    return h('div', { className: 'woact-context-menu', style: { left: props.value.x, top: props.value.y }, onClick: function (event) { event.stopPropagation(); } },
      h('button', { onClick: action('edit') }, h('span', { className: 'dashicons dashicons-edit' }), 'ویرایش'),
      h('button', { onClick: action('duplicate') }, h('span', { className: 'dashicons dashicons-admin-page' }), 'تکثیر'),
      h('button', { onClick: action('copy') }, h('span', { className: 'dashicons dashicons-clipboard' }), 'کپی'),
      h('button', { onClick: action('paste') }, h('span', { className: 'dashicons dashicons-editor-paste-text' }), 'چسباندن'),
      h('hr'), h('button', { onClick: action('lock') }, h('span', { className: 'dashicons dashicons-lock' }), 'قفل / بازکردن'),
      h('button', { className: 'is-danger', onClick: action('remove') }, h('span', { className: 'dashicons dashicons-trash' }), 'حذف')
    );
  }

  function HistoryPanel(props) {
    return h('aside', { className: 'woact-history-float' }, h('header', null, h('strong', null, 'تاریخچه تغییرات'), h('button', { onClick: props.close }, '×')), h('div', { className: 'woact-history-list' },
      props.undo.slice().reverse().map(function (_, index) { return h('div', { key: index }, h('span', { className: 'dashicons dashicons-backup' }), h('span', null, 'تغییر شماره ' + (props.undo.length - index)), h('small', null, 'قابل بازگردانی')); }),
      !props.undo.length ? h('p', null, 'هنوز تغییری ثبت نشده است.') : null
    ), h('footer', null, h('button', { disabled: !props.undo.length, onClick: props.undoAction }, 'بازگشت'), h('button', { disabled: !props.redo.length, onClick: props.redoAction }, 'انجام مجدد')));
  }

  function EditorNode(props) {
    var node = props.node, def = definition(props.manifest, node.type) || { label: node.type, icon: 'admin-generic' };
    var RuntimeNode = window.WoactPageRenderer && window.WoactPageRenderer.Node;
    var runtimeStyle = window.WoactPageRenderer && window.WoactPageRenderer.styleOf;
    var selected = props.selected === node.id, hidden = node.visibility && node.visibility[props.device] === false;
    var itemStyle = canContain(node) ? { flexGrow: Number((node.style || {}).flexGrow || 0) || undefined, flexBasis: (node.style || {}).flexGrow ? 0 : undefined, minWidth: 0 } : undefined;
    return h('div', { className: 'woact-editor-node ' + (selected ? 'is-selected ' : '') + (hidden ? 'is-hidden ' : '') + (node.locked ? 'is-locked' : ''), style: itemStyle, 'data-type': node.type, onClick: function (event) { event.stopPropagation(); props.select(node.id); }, onContextMenu: function (event) { props.context(node.id, event); }, draggable: !node.locked, onDragStart: function (event) { event.stopPropagation(); event.dataTransfer.setData('text/woact-page', JSON.stringify({ action: 'move', id: node.id })); }, onDragOver: function (event) { if (canContain(node)) { event.preventDefault(); event.currentTarget.classList.add('is-drop'); } }, onDragLeave: function (event) { event.currentTarget.classList.remove('is-drop'); }, onDrop: function (event) { if (!canContain(node)) return; event.preventDefault(); event.stopPropagation(); event.currentTarget.classList.remove('is-drop'); try { props.drop(node.id, JSON.parse(event.dataTransfer.getData('text/woact-page'))); } catch (ignore) {} } },
      h('div', { className: 'woact-editor-node__bar' }, h('span', { className: 'dashicons dashicons-' + def.icon }), def.label, node.locked ? h('span', { className: 'dashicons dashicons-lock' }) : null, h('span', { className: 'woact-editor-node__actions' }, h('button', { title: 'تکثیر', onClick: function (event) { event.stopPropagation(); props.duplicate(node.id); } }, '⧉'), h('button', { title: 'حذف', onClick: function (event) { event.stopPropagation(); props.remove(node.id); } }, '×'))),
      !canContain(node) ? h('div', { className: 'woact-editor-node__live' }, RuntimeNode ? h(RuntimeNode, { node: node, device: props.device, data: props.data }) : def.label) : null,
      canContain(node) ? h('div', { className: 'woact-editor-node__children', style: runtimeStyle ? runtimeStyle(node, props.device, (props.data || {}).designSystem || {}) : {} }, (node.children || []).map(function (child) { return h(EditorNode, Object.assign({}, props, { key: child.id, node: child })); }), !(node.children || []).length ? h('div', { className: 'woact-editor-drop' }, '+ المان را اینجا رها کنید') : null) : null
    );
  }

  function Canvas(props) {
    return h('main', { className: 'woact-page-canvas-shell device-' + props.device, onClick: function () { props.select(''); props.closeContext(); } }, h('div', { className: 'woact-page-canvas', style: { background: props.document.settings.background } },
      (props.document.nodes || []).map(function (node) { return h(EditorNode, Object.assign({}, props, { key: node.id, node: node })); }),
      h('div', { className: 'woact-editor-root-drop', onDragOver: function (event) { event.preventDefault(); }, onDrop: function (event) { event.preventDefault(); try { props.drop('', JSON.parse(event.dataTransfer.getData('text/woact-page'))); } catch (ignore) {} } }, h('button', { onClick: function () { props.structure(''); } }, '+ افزودن ساختار'))
    ));
  }

  function StructurePicker(props) {
    if (!props.open) return null;
    var flex = [[1], [1, 1], [1, 1, 1], [1, 2], [2, 1], [1, 1, 1, 1]];
    var grids = [{ c: 2, r: 2 }, { c: 3, r: 2 }, { c: 4, r: 2 }, { c: 3, r: 3 }];
    function preview(parts) { return h('span', { className: 'woact-structure-preview' }, parts.map(function (part, i) { return h('i', { key: i, style: { flex: part } }); })); }
    return h('div', { className: 'woact-structure-overlay', onClick: props.close }, h('section', { className: 'woact-structure-dialog', onClick: function (event) { event.stopPropagation(); } },
      h('header', null, h('strong', null, 'ساختار خود را انتخاب کنید'), h('button', { onClick: props.close }, '×')),
      h('div', { className: 'woact-structure-kinds' }, h('button', { className: props.kind === 'flex_container' ? 'is-active' : '', onClick: function () { props.kindChange('flex_container'); } }, h('span', { className: 'dashicons dashicons-align-pull-right' }), 'فلکس‌باکس'), h('button', { className: props.kind === 'grid_container' ? 'is-active' : '', onClick: function () { props.kindChange('grid_container'); } }, h('span', { className: 'dashicons dashicons-grid-view' }), 'شبکه‌ای')),
      props.kind === 'grid_container' ? h('div', { className: 'woact-structure-presets' }, grids.map(function (preset) { return h('button', { key: preset.c + 'x' + preset.r, onClick: function () { props.choose(preset); } }, h('span', { className: 'woact-grid-preview', style: { gridTemplateColumns: 'repeat(' + preset.c + ',1fr)' } }, Array.apply(null, Array(preset.c * preset.r)).map(function (_, i) { return h('i', { key: i }); })), preset.c + ' × ' + preset.r); })) : h('div', { className: 'woact-structure-presets' }, flex.map(function (parts, index) { return h('button', { key: index, onClick: function () { props.choose({ parts: parts }); } }, preview(parts)); }))
    ));
  }

  function Control(props) {
    var field = props.field || {}, value = props.value;
    if (window.WoactControls && field.type !== 'url') return window.WoactControls.render(field, value, props.change, { values: props.values || {}, device: props.device || 'desktop' });
    if (field.type === 'textarea') return h('textarea', { rows: 5, value: value || '', onChange: function (event) { props.change(event.target.value); } });
    if (field.type === 'number') return h('input', { type: 'number', value: value || 0, onChange: function (event) { props.change(Number(event.target.value)); } });
    if (field.type === 'url') return h('div', { className: 'woact-url-control' }, h('input', { type: 'url', dir: 'ltr', value: value || '', onChange: function (event) { props.change(event.target.value); } }), ['url', 'image', 'poster'].indexOf(field.id) !== -1 && wp.media ? h('button', { type: 'button', onClick: function () { var frame = wp.media({ title: 'انتخاب رسانه', multiple: false }); frame.on('select', function () { var attachment = frame.state().get('selection').first().toJSON(); props.change(attachment.url || ''); }); frame.open(); } }, 'رسانه') : null);
    if (field.type === 'select') { var options = (field.options || []).map(function (option) { return typeof option === 'string' ? option : option.value; }); if (!options.length) options = field.id === 'level' ? ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] : field.id === 'variant' ? ['primary', 'outline'] : field.id === 'tag' ? ['section', 'header', 'footer', 'main', 'article', 'aside'] : []; return h('select', { value: value || field.default, onChange: function (event) { props.change(event.target.value); } }, options.map(function (option) { return h('option', { key: option, value: option }, option); })); }
    return h('input', { value: value || '', onChange: function (event) { props.change(event.target.value); } });
  }
  function Field(props) { return h('label', { className: 'woact-page-field' }, h('span', null, props.label), props.children); }
  function Inspector(props) {
    var _tab = useState('content'), tab = _tab[0], setTab = _tab[1];
    var found = props.selected ? walk(props.document.nodes, props.selected, null) : null, node = found && found.node, def = node && definition(props.manifest, node.type);
    function styleField(label, key, type) { var source = props.device === 'desktop' ? node.style : (node.responsive[props.device] || {}), controlType = type === 'color' ? 'color' : 'number'; return h(Field, { label: label }, window.WoactControls ? window.WoactControls.render({ id: key, type: controlType, placeholder: props.device === 'desktop' ? '' : 'ارث‌بری' }, source[key], function (value) { props.changeStyle(key, value); }, { values: source, device: props.device }) : h('input', { type: type || 'number', value: source[key] === undefined ? '' : source[key], onChange: function (event) { props.changeStyle(key, type === 'number' || !type ? Number(event.target.value) : event.target.value); } })); }
    if (!node) return h('div', { className: 'woact-page-inspector' }, h('header', null, h('strong', null, 'تنظیمات صفحه')), h('div', { className: 'woact-page-inspector-tabs' }, [['content', 'تنظیمات'], ['style', 'استایل'], ['advanced', 'پیشرفته']].map(function (item) { return h('button', { key: item[0], className: tab === item[0] ? 'is-active' : '', onClick: function () { setTab(item[0]); } }, item[1]); })),
      tab === 'content' ? h('div', null,
        h(Field, { label: 'عنوان برگه' }, h('input', { value: props.page.title, onChange: function (event) { props.changePage('title', event.target.value); } })),
        h(Field, { label: 'وضعیت' }, h('select', { value: props.page.status || 'draft', onChange: function (event) { props.changePage('status', event.target.value); } }, h('option', { value: 'draft' }, 'پیش‌نویس'), h('option', { value: 'publish' }, 'منتشرشده'), h('option', { value: 'private' }, 'خصوصی'))),
        h(Field, { label: 'چیدمان صفحه' }, h('select', { value: props.document.settings.pageLayout || 'default', onChange: function (event) { props.changeSetting('pageLayout', event.target.value); } }, h('option', { value: 'default' }, 'پیش‌فرض پوسته'), h('option', { value: 'full_width' }, 'تمام‌عرض Woact'), h('option', { value: 'canvas' }, 'بوم خالی Woact'))),
        h(Field, { label: 'پنهان‌کردن عنوان' }, h('input', { type: 'checkbox', checked: props.document.settings.hideTitle !== false, onChange: function (event) { props.changeSetting('hideTitle', event.target.checked); } })),
        h(Field, { label: 'فعال‌سازی صفحه React' }, h('input', { type: 'checkbox', checked: props.page.enabled, onChange: function (event) { props.changePage('enabled', event.target.checked); } }))) : null,
      tab === 'style' ? h('div', null, h(Field, { label: 'عرض محتوا' }, h('input', { type: 'number', value: props.document.settings.contentWidth, onChange: function (event) { props.changeSetting('contentWidth', Number(event.target.value)); } })), h(Field, { label: 'پس‌زمینه' }, h('input', { type: 'color', value: props.document.settings.background, onChange: function (event) { props.changeSetting('background', event.target.value); } }))) : null,
      tab === 'advanced' ? h('div', null, h(Field, { label: 'کلاس CSS صفحه' }, h('input', { value: props.document.settings.className || '', onChange: function (event) { props.changeSetting('className', event.target.value); } })), h(Field, { label: 'CSS سفارشی صفحه' }, h('textarea', { rows: 9, dir: 'ltr', value: props.document.settings.customCss || '', onChange: function (event) { props.changeSetting('customCss', event.target.value); } }))) : null
    );
    function responsiveControls() { return h('div', null, h('p', { className: 'woact-responsive-note' }, 'نمایش یا پنهان‌سازی این المان در هر دستگاه'), ['desktop', 'tablet', 'mobile'].map(function (device) { return h('label', { className: 'woact-check', key: device }, h('input', { type: 'checkbox', checked: node.visibility[device] !== false, onChange: function (event) { props.changeVisibility(device, event.target.checked); } }), { desktop: 'دسکتاپ', tablet: 'تبلت', mobile: 'موبایل' }[device]); })); }
    var designPanel = window.WoactDesignSystem && window.WoactDesignSystem.Panel;
    var designValues = Object.assign({}, window.WoactDesignSystem ? window.WoactDesignSystem.defaults() : {}, node.design || {});
    return h('div', { className: 'woact-page-inspector' }, h('header', null, h('button', { className: 'woact-panel-back', onClick: function () { props.select(''); }, title: 'بازگشت به ابزارک‌ها' }, h('span', { className: 'dashicons dashicons-arrow-right-alt2' })), h('strong', null, 'ویرایش ' + (def ? def.label : node.type)), h('code', null, node.id)), h('div', { className: 'woact-page-inspector-tabs' }, [['content', 'محتوا'], ['style', 'استایل'], ['advanced', 'پیشرفته']].map(function (item) { return h('button', { key: item[0], className: tab === item[0] ? 'is-active' : '', onClick: function () { setTab(item[0]); } }, item[1]); })),
      tab === 'content' ? h('div', null, (def.fields || []).map(function (field) { return h(Field, { key: field.id, label: field.label }, h(Control, { field: field, value: node.props[field.id], values: node.props, device: props.device, change: function (value) { props.changeProp(field.id, value); } })); }), node.type === 'flex_container' ? h('div', { className: 'woact-layout-controls' }, h('h3', null, 'طرح‌بندی Flexbox'), h(Field, { label: 'جهت' }, h('select', { value: node.style.direction || 'row', onChange: function (event) { props.changeStyle('direction', event.target.value); } }, ['row', 'column', 'row-reverse', 'column-reverse'].map(function (v) { return h('option', { value: v, key: v }, v); }))), h(Field, { label: 'شکستن خط' }, h('select', { value: node.style.wrap || 'nowrap', onChange: function (event) { props.changeStyle('wrap', event.target.value); } }, ['nowrap', 'wrap', 'wrap-reverse'].map(function (v) { return h('option', { value: v, key: v }, v); }))), styleField('فاصله', 'gap'), h(Field, { label: 'تراز افقی' }, h('select', { value: node.style.justify || 'start', onChange: function (event) { props.changeStyle('justify', event.target.value); } }, ['start', 'center', 'end', 'between', 'around', 'evenly'].map(function (v) { return h('option', { value: v, key: v }, v); }))), h(Field, { label: 'تراز عمودی' }, h('select', { value: node.style.align || 'stretch', onChange: function (event) { props.changeStyle('align', event.target.value); } }, ['start', 'center', 'end', 'stretch'].map(function (v) { return h('option', { value: v, key: v }, v); })))) : null, node.type === 'grid_container' ? h('div', { className: 'woact-layout-controls' }, h('h3', null, 'طرح‌بندی Grid'), styleField('تعداد ستون', 'gridColumns'), styleField('تعداد ردیف خودکار', 'gridRows'), styleField('فاصله ستون', 'columnGap'), styleField('فاصله ردیف', 'rowGap')) : null, !canContain(node) ? h(Field, { label: 'داده پویا' }, h('select', { value: node.dynamic || '', onChange: function (event) { props.changeNode('dynamic', event.target.value); } }, (props.manifest.dynamicSources || []).map(function (item) { return h('option', { key: item.value, value: item.value }, item.label); }))) : null) : null,
      tab === 'style' ? (designPanel ? h(designPanel, { tab: 'style', values: designValues, globals: props.document.settings.designSystem || {}, device: props.device, change: props.changeDesign, responsive: responsiveControls }) : h('div', null, styleField('رنگ متن', 'color', 'color'))) : null,
      tab === 'advanced' ? h('div', null, h(Field, { label: 'قفل ویرایش' }, h('input', { type: 'checkbox', checked: !!node.locked, onChange: function (event) { props.changeNode('locked', event.target.checked); } })), designPanel ? h(designPanel, { tab: 'advanced', values: designValues, globals: props.document.settings.designSystem || {}, device: props.device, change: props.changeDesign, responsive: responsiveControls }) : responsiveControls()) : null
    );
  }

  function App() {
    var _data = useState(null), data = _data[0], setData = _data[1];
    var _page = useState(null), page = _page[0], setPage = _page[1];
    var _saved = useState(null), saved = _saved[0], setSaved = _saved[1];
    var _selected = useState(''), selected = _selected[0], setSelected = _selected[1];
    var _device = useState('desktop'), device = _device[0], setDevice = _device[1];
    var _notice = useState(null), notice = _notice[0], setNotice = _notice[1];
    var _saving = useState(false), saving = _saving[0], setSaving = _saving[1];
    var _panelOpen = useState(true), panelOpen = _panelOpen[0], setPanelOpen = _panelOpen[1];
    var _navigatorOpen = useState(false), navigatorOpen = _navigatorOpen[0], setNavigatorOpen = _navigatorOpen[1];
    var _historyOpen = useState(false), historyOpen = _historyOpen[0], setHistoryOpen = _historyOpen[1];
    var _contextMenu = useState(null), contextMenu = _contextMenu[0], setContextMenu = _contextMenu[1];
    var _publishing = useState(false), publishing = _publishing[0], setPublishing = _publishing[1];
    var _structure = useState(null), structure = _structure[0], setStructure = _structure[1];
    var undo = useRef([]), redo = useRef([]), clipboard = useRef(null);
    var importRef = useRef(null);
    var saveInFlight = useRef(null);
    var dirty = page && saved ? !same(page, saved) : false;
    useEffect(function () { apiFetch({ path: '/woact/v1/admin/pages' }).then(function (value) { setData(value); var requested = Number(config.initialPostId || 0); if (requested) load(requested); else if (value.pages[0]) load(value.pages[0].id); }).catch(function (error) { setNotice({ type: 'error', message: error.message || 'اطلاعات صفحه‌ساز بارگذاری نشد.' }); }); }, []);
    useEffect(function () { if (!page) return; try { localStorage.setItem('woact-page-backup-' + page.id, JSON.stringify({ savedAt: Date.now(), title: page.title, document: page.draft })); } catch (ignore) {} }, [page]);
    useEffect(function () { if (!dirty || !page) return; var timer = setTimeout(function () { save(true).catch(function () {}); }, 30000); return function () { clearTimeout(timer); }; }, [page, dirty]);
    useEffect(function () { function close() { setContextMenu(null); } window.addEventListener('blur', close); return function () { window.removeEventListener('blur', close); }; }, []);
    function load(id) { return apiFetch({ path: '/woact/v1/admin/pages/' + id }).then(function (value) { setPage(clone(value)); setSaved(clone(value)); setSelected(''); undo.current = []; redo.current = []; return value; }).catch(function (error) { setNotice({ type: 'error', message: error.message || 'این نوشته برای ویرایش با Woact بارگذاری نشد.' }); return null; }); }
    function commit(mutator) { if (!page) return; undo.current.push(clone(page)); if (undo.current.length > 60) undo.current.shift(); redo.current = []; var next = clone(page); mutator(next); setPage(next); }
    function history(direction) { var source = direction === 'undo' ? undo : redo, target = direction === 'undo' ? redo : undo; if (!source.current.length) return; target.current.push(clone(page)); setPage(source.current.pop()); }
    function create() { var title = window.prompt('عنوان صفحه جدید:', 'صفحه React جدید'); if (!title) return; apiFetch({ path: '/woact/v1/admin/pages', method: 'POST', data: { title: title } }).then(function (value) { setData(function (current) { return Object.assign({}, current, { pages: [{ id: value.id, title: value.title, status: value.status, enabled: true, hasDocument: true }].concat(current.pages) }); }); setPage(clone(value)); setSaved(clone(value)); }); }
    function save(silent) {
      if (!page) return Promise.resolve(null);
      if (saveInFlight.current) return saveInFlight.current;
      setSaving(true);
      var request = apiFetch({ path: '/woact/v1/admin/pages/' + page.id, method: 'POST', data: { title: page.title, status: page.status, enabled: page.enabled, document: page.draft, revision: page.revision } })
        .then(function (value) { setPage(clone(value)); setSaved(clone(value)); if (!silent) setNotice({ type: value.conflictResolved ? 'warning' : 'success', message: value.conflictResolved ? 'نسخهٔ سرور در تاریخچه نگه‌داری و تغییرات جدید ذخیره شد.' : 'پیش‌نویس ذخیره شد.' }); return value; })
        .catch(function (error) { setNotice({ type: 'error', message: error.message || 'ذخیره انجام نشد.' }); throw error; })
        .finally(function () { saveInFlight.current = null; setSaving(false); });
      saveInFlight.current = request;
      return request;
    }
    function publish() {
      if (publishing) return;
      setPublishing(true);
      (saveInFlight.current || save(true))
        .then(function (value) { return value ? apiFetch({ path: '/woact/v1/admin/pages/' + value.id + '/publish', method: 'POST' }) : null; })
        .then(function (value) { if (!value) return; setPage(clone(value)); setSaved(clone(value)); setNotice({ type: 'success', message: 'صفحه React منتشر شد.' }); })
        .catch(function (error) { setNotice({ type: 'error', message: error.message || 'انتشار انجام نشد.' }); })
        .finally(function () { setPublishing(false); });
    }
    function exportJson() { var payload = { product: 'woact-page', schemaVersion: data.manifest.schemaVersion, version: data.version, title: page.title, document: page.draft }; var url = URL.createObjectURL(new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' })); var link = document.createElement('a'); link.href = url; link.download = 'woact-page-' + page.id + '.json'; link.click(); URL.revokeObjectURL(url); }
    function aiJson() {
      return {
        product: 'woact-page-ai-request', schemaVersion: data.manifest.schemaVersion, version: data.version,
        language: 'fa-IR', direction: 'rtl',
        aiInstructions: [
          'بر اساس توضیح من یک صفحه حرفه‌ای و واکنش‌گرا طراحی کن.',
          'خروجی نهایی فقط JSON معتبر و بدون Markdown یا توضیح باشد.',
          'در خروجی product باید woact-page و schemaVersion باید برابر مقدار همین فایل باشد.',
          'ساختار قابل درون‌ریزی برابر {product,schemaVersion,version,title,document} است.',
          'فقط از typeهای موجود در manifest.components استفاده کن و هر id در کل صفحه یکتا باشد.',
          'برای ساختارهای جدید فقط flex_container و grid_container بساز؛ انواع قدیمی فقط برای سازگاری هستند.',
          'برای موبایل و تبلت responsive و visibility را حفظ کن و متن‌ها را فارسی بنویس.',
          'هیچ JavaScript، iframe ناشناس، shortcode ناشناس یا داده محرمانه تولید نکن.'
        ],
        userBrief: 'اینجا نوع سایت، رنگ‌ها، بخش‌ها، محتوا و سبک دلخواه را برای هوش مصنوعی بنویسید.',
        outputContract: {
          product: 'woact-page', schemaVersion: data.manifest.schemaVersion, version: data.version,
          title: 'عنوان صفحه', document: { schemaVersion: data.manifest.schemaVersion, settings: { contentWidth: 1180, background: '#ffffff', className: '', hideTitle: true, designSystem: window.WoactGlobalDesign ? window.WoactGlobalDesign.defaults() : {} }, nodes: [] }
        },
        nodeContract: {
          required: ['id', 'type', 'props', 'style', 'responsive', 'design', 'visibility', 'dynamic', 'locked', 'children'],
          containers: ['flex_container', 'grid_container'],
          styleFields: ['display', 'direction', 'wrap', 'gap', 'columnGap', 'rowGap', 'gridColumns', 'gridRows', 'padding', 'margin', 'background', 'color', 'width', 'maxWidth', 'minHeight', 'align', 'justify', 'justifyItems', 'alignItems', 'fontSize', 'fontWeight', 'radius', 'borderWidth', 'borderColor', 'shadow', 'zIndex'],
          designFields: ['typography', 'color', 'backgroundControl', 'borderControl', 'marginBox', 'paddingBox', 'widthUnit', 'minHeightUnit', 'position', 'zIndex', 'entranceAnimation', 'animationDuration', 'animationDelay', 'hoverAnimation', 'translateX', 'translateY', 'scale', 'rotate', 'skewX', 'skewY', 'maskShape', 'maskSize', 'maskPosition', 'cssId', 'cssClasses', 'attributes', 'customCss'],
          responsiveDevices: ['tablet', 'mobile'], visibilityDevices: ['desktop', 'tablet', 'mobile']
        },
        manifest: { categories: data.manifest.categories, components: data.manifest.components, dynamicSources: data.manifest.dynamicSources },
        currentDocument: { product: 'woact-page', schemaVersion: data.manifest.schemaVersion, version: data.version, title: page.title, document: page.draft }
      };
    }
    function exportAiJson() {
      var content = JSON.stringify(aiJson(), null, 2);
      var url = URL.createObjectURL(new Blob([content], { type: 'application/json' }));
      var link = document.createElement('a'); link.href = url; link.download = 'woact-ai-page-request-' + page.id + '.json'; link.click(); URL.revokeObjectURL(url);
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(content).then(function () { setNotice({ type: 'success', message: 'JSON راهنمای هوش مصنوعی دانلود و در کلیپ‌بورد کپی شد.' }); }).catch(function () { setNotice({ type: 'success', message: 'JSON راهنمای هوش مصنوعی دانلود شد.' }); });
      else setNotice({ type: 'success', message: 'JSON راهنمای هوش مصنوعی دانلود شد.' });
    }
    function importJson(event) { var file = event.target.files && event.target.files[0]; event.target.value = ''; if (!file) return; file.text().then(JSON.parse).then(function (payload) { if (payload.product !== 'woact-page' || !payload.document || Number(payload.schemaVersion || 0) > data.manifest.schemaVersion) throw new Error('ساختار فایل معتبر نیست.'); commit(function (next) { next.draft = payload.document; if (payload.title) next.title = payload.title; }); setNotice({ type: 'success', message: 'JSON وارد شد؛ برای اعتبارسنجی نهایی ذخیره کنید.' }); }).catch(function (error) { setNotice({ type: 'error', message: error.message || 'فایل معتبر نیست.' }); }); }
    function add(type) { commit(function (next) { var item = makeNode(data.manifest, type); var target = selected && walk(next.draft.nodes, selected, null); if (target && canContain(target.node)) target.node.children.push(item); else next.draft.nodes.push(item); setSelected(item.id); }); }
    function openStructure(kind) { setStructure({ kind: kind || 'flex_container' }); }
    function addStructure(preset) { var kind = structure.kind; commit(function (next) { var root = makeNode(data.manifest, kind), target = selected && walk(next.draft.nodes, selected, null), list = target && canContain(target.node) ? target.node.children : next.draft.nodes; if (kind === 'grid_container') { root.style.gridColumns = preset.c || 2; root.style.gridRows = preset.r || 0; } else { root.style.direction = 'row'; (preset.parts || [1]).forEach(function (part) { var child = makeNode(data.manifest, 'flex_container'); child.style.direction = 'column'; child.style.padding = 16; child.style.flexGrow = part; root.children.push(child); }); } list.push(root); setSelected(root.id); }); setStructure(null); }
    function drop(targetId, info) { commit(function (next) { var target = targetId ? walk(next.draft.nodes, targetId, null) : null, list = target && canContain(target.node) ? target.node.children : next.draft.nodes; if (info.action === 'new') list.push(makeNode(data.manifest, info.type)); if (info.action === 'move') { var source = walk(next.draft.nodes, info.id, null); if (!source || (target && contains(source.node, target.node.id))) return; var item = source.list.splice(source.index, 1)[0]; list.push(item); } }); }
    function remove(id) { var found = walk(page.draft.nodes, id, null); if (!found || found.node.locked) return; commit(function (next) { var item = walk(next.draft.nodes, id, null); item.list.splice(item.index, 1); }); setSelected(''); }
    function duplicate(id) { if (!page || !walk(page.draft.nodes, id, null)) return; commit(function (next) { var found = walk(next.draft.nodes, id, null), copy = renewIds(clone(found.node)); found.list.splice(found.index + 1, 0, copy); }); }
    function copyNode(id) { var found = page && walk(page.draft.nodes, id, null); if (found) { clipboard.current = clone(found.node); setNotice({ type: 'success', message: 'المان کپی شد.' }); } }
    function pasteNode(id) { if (!clipboard.current) return; commit(function (next) { var copy = renewIds(clone(clipboard.current)), target = id && walk(next.draft.nodes, id, null); (target && canContain(target.node) ? target.node.children : next.draft.nodes).push(copy); setSelected(copy.id); }); }
    function toggleLock(id) { commit(function (next) { var found = walk(next.draft.nodes, id, null); if (found) found.node.locked = !found.node.locked; }); }
    function openContext(id, event) { event.preventDefault(); event.stopPropagation(); setSelected(id); setContextMenu({ id: id, x: Math.min(event.clientX, window.innerWidth - 190), y: Math.min(event.clientY, window.innerHeight - 260) }); }
    function contextAction(action, id) { if (action === 'edit') setSelected(id); if (action === 'duplicate') duplicate(id); if (action === 'copy') copyNode(id); if (action === 'paste') pasteNode(id); if (action === 'lock') toggleLock(id); if (action === 'remove') remove(id); }
    function changeNode(key, value) { commit(function (next) { walk(next.draft.nodes, selected, null).node[key] = value; }); }
    function changeProp(key, value) { commit(function (next) { walk(next.draft.nodes, selected, null).node.props[key] = value; }); }
    function changeStyle(key, value) { commit(function (next) { var node = walk(next.draft.nodes, selected, null).node; if (device === 'desktop') node.style[key] = value; else node.responsive[device][key] = value; }); }
    function changeDesign(key, value) { commit(function (next) { var node = walk(next.draft.nodes, selected, null).node; node.design = Object.assign({}, node.design || {}); node.design[key] = value; }); }
    function changeVisibility(target, value) { commit(function (next) { walk(next.draft.nodes, selected, null).node.visibility[target] = value; }); }
    function changePage(key, value) { commit(function (next) { next[key] = value; }); }
    function changeSetting(key, value) { commit(function (next) { next.draft.settings[key] = value; }); }
    function changeGlobal(value) { changeSetting('designSystem', value); }
    function keydown(event) { var tag = event.target && event.target.tagName; if (['INPUT', 'TEXTAREA', 'SELECT'].indexOf(tag) !== -1 && !(event.ctrlKey || event.metaKey)) return; var key = event.key.toLowerCase(); if ((event.ctrlKey || event.metaKey) && key === 's') { event.preventDefault(); save(false).catch(function () {}); } if ((event.ctrlKey || event.metaKey) && key === 'p') { event.preventDefault(); setPanelOpen(function (value) { return !value; }); } if ((event.ctrlKey || event.metaKey) && key === 'z') { event.preventDefault(); history(event.shiftKey ? 'redo' : 'undo'); } if ((event.ctrlKey || event.metaKey) && key === 'c' && selected) copyNode(selected); if ((event.ctrlKey || event.metaKey) && key === 'v' && clipboard.current) { event.preventDefault(); pasteNode(selected); } if ((event.ctrlKey || event.metaKey) && key === 'd' && selected) { event.preventDefault(); duplicate(selected); } if ((event.key === 'Delete' || event.key === 'Backspace') && selected && tag !== 'INPUT' && tag !== 'TEXTAREA') { event.preventDefault(); remove(selected); } if (event.key === 'Escape') { setContextMenu(null); setSelected(''); } }
    useEffect(function () { window.addEventListener('keydown', keydown); return function () { window.removeEventListener('keydown', keydown); }; }, [page, selected, device, dirty, publishing]);
    if (!data) return h('div', { className: 'woact-page-loading' }, 'در حال بارگذاری صفحه‌ساز…');
    if (!page) return h('div', { className: 'woact-page-loading' }, h('span', { className: 'spinner is-active' }), h('p', null, notice ? notice.message : 'در حال بازکردن مستقیم محتوا در ویرایشگر Woact…'));
    var dynamicData = { site: config.site || {}, post: { title: page.title, excerpt: 'خلاصه نمونه صفحه', featuredImage: '' }, user: config.user || {}, widgets: config.widgetData || {}, designSystem: page.draft.settings.designSystem || {} };
    return h('div', { className: 'woact-page-builder ' + (panelOpen ? '' : 'is-panel-hidden'), onClick: function () { if (contextMenu) setContextMenu(null); } },
      h('header', { className: 'woact-page-topbar' },
        h('div', { className: 'woact-page-brand' }, h('button', { className: 'woact-logo-button', onClick: function () { setPanelOpen(function (value) { return !value; }); }, title: 'نمایش یا مخفی‌کردن پنل (Ctrl+P)' }, 'W'), h('span', null, h('strong', null, 'WOACT'), h('small', null, data.version))),
        h('div', { className: 'woact-toolbar-group' },
          h('button', { className: !selected && panelOpen ? 'is-active' : '', onClick: function () { setPanelOpen(true); setSelected(''); }, title: 'افزودن المان' }, h('span', { className: 'dashicons dashicons-plus-alt2' })),
          h('button', { onClick: exportAiJson, title: 'JSON برای هوش مصنوعی' }, h('span', { className: 'dashicons dashicons-superhero-alt' })),
          h('button', { className: historyOpen ? 'is-active' : '', onClick: function () { setHistoryOpen(!historyOpen); }, title: 'تاریخچه' }, h('span', { className: 'dashicons dashicons-backup' })),
          h('button', { className: selected === '__page__' ? 'is-active' : '', onClick: function () { setPanelOpen(true); setSelected('__page__'); }, title: 'تنظیمات صفحه' }, h('span', { className: 'dashicons dashicons-admin-generic' }))
        ),
        h('div', { className: 'woact-current-document' }, h('span', { className: 'dashicons dashicons-arrow-down-alt2' }), h('strong', null, page.title)),
        h(Devices, { value: device, change: setDevice }),
        h('div', { className: 'woact-page-actions' },
          h('button', { disabled: !undo.current.length, onClick: function () { history('undo'); }, title: 'بازگشت' }, h('span', { className: 'dashicons dashicons-undo' })),
          h('button', { disabled: !redo.current.length, onClick: function () { history('redo'); }, title: 'انجام مجدد' }, h('span', { className: 'dashicons dashicons-redo' })),
          h('button', { className: navigatorOpen ? 'is-active' : '', onClick: function () { setNavigatorOpen(!navigatorOpen); }, title: 'ساختار صفحه' }, h('span', { className: 'dashicons dashicons-editor-table' })),
          h('a', { href: page.viewUrl, target: '_blank', rel: 'noreferrer', title: 'پیش‌نمایش' }, h('span', { className: 'dashicons dashicons-visibility' })),
          h('button', { className: 'woact-more-button', onClick: exportJson, title: 'خروجی JSON' }, h('span', { className: 'dashicons dashicons-download' })),
          h('button', { className: 'woact-more-button', onClick: function () { importRef.current.click(); }, title: 'درون‌ریزی JSON' }, h('span', { className: 'dashicons dashicons-upload' })),
          h('input', { ref: importRef, type: 'file', hidden: true, accept: '.json,application/json', onChange: importJson }),
          h('button', { className: 'woact-save-button', disabled: !dirty || saving || publishing, onClick: function () { save(false).catch(function () {}); } }, saving ? 'ذخیره…' : 'ذخیره'),
          h('button', { className: 'is-primary', disabled: publishing, onClick: publish }, publishing ? 'در حال انتشار…' : 'انتشار')
        )
      ),
      h('div', { className: 'woact-page-status' }, h('span', { className: 'is-live' }, '● بوم زنده React'), h('span', { className: saving || publishing ? 'is-saving' : dirty ? 'is-dirty' : 'is-saved' }, saving || publishing ? 'در حال ذخیره' : dirty ? 'تغییرات ذخیره‌نشده' : 'ذخیره شده'), h('span', null, device === 'desktop' ? 'نمای دسکتاپ' : device === 'tablet' ? 'نمای تبلت' : 'نمای موبایل'), h('span', { className: 'woact-shortcuts' }, 'Ctrl+S · Ctrl+Z · Ctrl+P')),
      h(Notice, { value: notice, close: function () { setNotice(null); } }),
      h('div', { className: 'woact-page-body' },
        panelOpen ? h('aside', { className: 'woact-unified-panel' }, h('header', { className: 'woact-panel-header' }, h('button', { onClick: function () { setPanelOpen(false); }, title: 'بستن پنل' }, h('span', { className: 'dashicons dashicons-no-alt' })), h('strong', null, selected === '__page__' ? 'تنظیمات صفحه' : selected ? 'تنظیمات المان' : 'المان‌ها'), h('button', { onClick: function () { setSelected(''); }, title: 'پنل ابزارک‌ها' }, h('span', { className: 'dashicons dashicons-screenoptions' }))), selected ? h(Inspector, { manifest: data.manifest, page: page, document: page.draft, selected: selected, select: setSelected, device: device, changeNode: changeNode, changeProp: changeProp, changeStyle: changeStyle, changeDesign: changeDesign, changeVisibility: changeVisibility, changePage: changePage, changeSetting: changeSetting }) : h(Palette, { manifest: data.manifest, document: page.draft, add: add, structure: openStructure, select: setSelected, changeGlobal: changeGlobal })) : null,
        h(Canvas, { manifest: data.manifest, document: page.draft, selected: selected, select: setSelected, device: device, data: dynamicData, add: add, structure: openStructure, drop: drop, remove: remove, duplicate: duplicate, context: openContext, closeContext: function () { setContextMenu(null); } })
      ),
      navigatorOpen ? h(Navigator, { manifest: data.manifest, document: page.draft, selected: selected, select: setSelected, device: device, context: openContext, close: function () { setNavigatorOpen(false); } }) : null,
      historyOpen ? h(HistoryPanel, { undo: undo.current, redo: redo.current, close: function () { setHistoryOpen(false); }, undoAction: function () { history('undo'); }, redoAction: function () { history('redo'); } }) : null,
      h(ContextMenu, { value: contextMenu, close: function () { setContextMenu(null); }, run: contextAction }),
      h(StructurePicker, { open: !!structure, kind: structure && structure.kind, kindChange: function (kind) { setStructure({ kind: kind }); }, choose: addStructure, close: function () { setStructure(null); } }),
      h('button', { className: 'woact-panel-switcher', title: 'نمایش یا مخفی‌کردن پنل (Ctrl+P)', onClick: function () { setPanelOpen(!panelOpen); } }, h('span', { className: 'dashicons dashicons-arrow-' + (panelOpen ? 'right' : 'left') + '-alt2' }))
    );
  }
  var root = document.getElementById('woact-page-builder-root');
  if (!root) return;
  if (e.createRoot) e.createRoot(root).render(h(App)); else e.render(h(App), root);
})(window.wp, window.WoactPageBuilderConfig || {});
