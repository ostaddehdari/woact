(function (wp, config) {
  'use strict';
  if (!wp || !wp.element || !wp.apiFetch) return;
  var e = wp.element, h = e.createElement, Fragment = e.Fragment;
  var useState = e.useState, useEffect = e.useEffect, useRef = e.useRef;
  var apiFetch = wp.apiFetch;
  if (config.nonce && apiFetch.createNonceMiddleware) apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
  function clone(v) { return JSON.parse(JSON.stringify(v)); }
  function same(a, b) { return JSON.stringify(a) === JSON.stringify(b); }
  function uid(type) { return type + '_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7); }
  function visibility() { return { desktop: true, tablet: true, mobile: true }; }
  function definition(manifest, type) { return (manifest.components || []).find(function (item) { return item.type === type; }); }
  function walk(nodes, id, parent) { for (var i = 0; i < nodes.length; i++) { if (nodes[i].id === id) return { node: nodes[i], parent: parent, list: nodes, index: i }; var found = walk(nodes[i].children || [], id, nodes[i]); if (found) return found; } return null; }
  function contains(node, id) { if (!node) return false; if (node.id === id) return true; return (node.children || []).some(function (child) { return contains(child, id); }); }
  function renewIds(node) { node.id = uid(node.type); (node.children || []).forEach(renewIds); return node; }
  function canContain(node) { return ['section', 'container', 'columns', 'column'].indexOf(node && node.type) !== -1; }
  function makeNode(manifest, type) { var def = definition(manifest, type) || {}; return { id: uid(type), type: type, props: clone(def.defaults || {}), style: { display: canContain({ type: type }) ? 'flex' : 'block', direction: type === 'columns' ? 'row' : 'column', gap: type === 'columns' ? 20 : 16, padding: type === 'section' ? 40 : 0, margin: 0, background: '', color: '', width: 0, maxWidth: type === 'section' ? 1180 : 0, minHeight: type === 'section' ? 120 : 0, align: 'stretch', justify: 'start', fontSize: type === 'heading' ? 36 : 0, fontWeight: type === 'heading' ? 700 : 0, radius: 0, borderWidth: 0, borderColor: '', shadow: '', zIndex: 0 }, responsive: { tablet: {}, mobile: {} }, visibility: visibility(), dynamic: '', locked: false, children: [] }; }

  function Notice(props) { return props.value ? h('div', { className: 'woact-page-notice is-' + props.value.type }, props.value.message, h('button', { onClick: props.close }, '×')) : null; }
  function Devices(props) { return h('div', { className: 'woact-page-devices' }, [['desktop', 'desktop', 'دسکتاپ'], ['tablet', 'tablet', 'تبلت'], ['mobile', 'smartphone', 'موبایل']].map(function (item) { return h('button', { key: item[0], className: props.value === item[0] ? 'is-active' : '', onClick: function () { props.change(item[0]); } }, h('span', { className: 'dashicons dashicons-' + item[1] }), item[2]); })); }

  function Palette(props) {
    var _tab = useState('elements'), tab = _tab[0], setTab = _tab[1];
    var _query = useState(''), query = _query[0], setQuery = _query[1];
    function nav(nodes, depth) { return nodes.map(function (node) { var def = definition(props.manifest, node.type); return h('div', { key: node.id, className: 'woact-page-nav-item', style: { '--depth': depth } }, h('button', { onClick: function () { props.select(node.id); } }, h('span', { className: 'dashicons dashicons-' + (def ? def.icon : 'admin-generic') }), def ? def.label : node.type), node.children && node.children.length ? nav(node.children, depth + 1) : null); }); }
    return h('aside', { className: 'woact-page-palette' }, h('div', { className: 'woact-page-tabs' }, h('button', { className: tab === 'elements' ? 'is-active' : '', onClick: function () { setTab('elements'); } }, 'المان‌ها'), h('button', { className: tab === 'navigator' ? 'is-active' : '', onClick: function () { setTab('navigator'); } }, 'ناوبری')),
      tab === 'navigator' ? h('div', { className: 'woact-page-navigator' }, nav(props.document.nodes || [], 0)) : h(Fragment, null,
        h('div', { className: 'woact-page-search' }, h('span', { className: 'dashicons dashicons-search' }), h('input', { value: query, placeholder: 'جست‌وجوی المان…', onChange: function (event) { setQuery(event.target.value); } })),
        (props.manifest.categories || []).map(function (cat) { var items = (props.manifest.components || []).filter(function (item) { return item.category === cat.id && (!query || item.label.indexOf(query) !== -1 || item.type.indexOf(query.toLowerCase()) !== -1); }); return items.length ? h('section', { key: cat.id }, h('h3', null, cat.label), h('div', { className: 'woact-page-elements' }, items.map(function (item) { return h('button', { key: item.type, draggable: true, onDragStart: function (event) { event.dataTransfer.setData('text/woact-page', JSON.stringify({ action: 'new', type: item.type })); }, onClick: function () { props.add(item.type); } }, h('span', { className: 'dashicons dashicons-' + item.icon }), item.label); }))) : null; })
      )
    );
  }

  function EditorNode(props) {
    var node = props.node, def = definition(props.manifest, node.type) || { label: node.type, icon: 'admin-generic' };
    var RuntimeNode = window.WoactPageRenderer && window.WoactPageRenderer.Node;
    var runtimeStyle = window.WoactPageRenderer && window.WoactPageRenderer.styleOf;
    var selected = props.selected === node.id, hidden = node.visibility && node.visibility[props.device] === false;
    return h('div', { className: 'woact-editor-node ' + (selected ? 'is-selected ' : '') + (hidden ? 'is-hidden ' : '') + (node.locked ? 'is-locked' : ''), 'data-type': node.type, onClick: function (event) { event.stopPropagation(); props.select(node.id); }, draggable: !node.locked, onDragStart: function (event) { event.stopPropagation(); event.dataTransfer.setData('text/woact-page', JSON.stringify({ action: 'move', id: node.id })); }, onDragOver: function (event) { if (canContain(node)) { event.preventDefault(); event.currentTarget.classList.add('is-drop'); } }, onDragLeave: function (event) { event.currentTarget.classList.remove('is-drop'); }, onDrop: function (event) { if (!canContain(node)) return; event.preventDefault(); event.stopPropagation(); event.currentTarget.classList.remove('is-drop'); try { props.drop(node.id, JSON.parse(event.dataTransfer.getData('text/woact-page'))); } catch (ignore) {} } },
      h('div', { className: 'woact-editor-node__bar' }, h('span', { className: 'dashicons dashicons-' + def.icon }), def.label, node.locked ? h('span', { className: 'dashicons dashicons-lock' }) : null, h('span', { className: 'woact-editor-node__actions' }, h('button', { title: 'تکثیر', onClick: function (event) { event.stopPropagation(); props.duplicate(node.id); } }, '⧉'), h('button', { title: 'حذف', onClick: function (event) { event.stopPropagation(); props.remove(node.id); } }, '×'))),
      !canContain(node) ? h('div', { className: 'woact-editor-node__live' }, RuntimeNode ? h(RuntimeNode, { node: node, device: props.device, data: props.data }) : def.label) : null,
      canContain(node) ? h('div', { className: 'woact-editor-node__children', style: runtimeStyle ? runtimeStyle(node, props.device) : {} }, (node.children || []).map(function (child) { return h(EditorNode, Object.assign({}, props, { key: child.id, node: child })); }), !(node.children || []).length ? h('div', { className: 'woact-editor-drop' }, '+ المان را اینجا رها کنید') : null) : null
    );
  }

  function Canvas(props) {
    return h('main', { className: 'woact-page-canvas-shell device-' + props.device, onClick: function () { props.select(''); } }, h('div', { className: 'woact-page-canvas', style: { background: props.document.settings.background } },
      (props.document.nodes || []).map(function (node) { return h(EditorNode, Object.assign({}, props, { key: node.id, node: node })); }),
      h('div', { className: 'woact-editor-root-drop', onDragOver: function (event) { event.preventDefault(); }, onDrop: function (event) { event.preventDefault(); try { props.drop('', JSON.parse(event.dataTransfer.getData('text/woact-page'))); } catch (ignore) {} } }, h('button', { onClick: function () { props.add('section'); } }, '+ افزودن بخش'))
    ));
  }

  function Control(props) {
    var field = props.field || {}, value = props.value;
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
    function styleField(label, key, type) { var source = props.device === 'desktop' ? node.style : (node.responsive[props.device] || {}); return h(Field, { label: label }, type === 'color' ? h('div', { className: 'woact-color' }, h('input', { type: 'color', value: source[key] || '#ffffff', onChange: function (event) { props.changeStyle(key, event.target.value); } }), h('input', { dir: 'ltr', value: source[key] || '', onChange: function (event) { props.changeStyle(key, event.target.value); } })) : h('input', { type: type || 'number', value: source[key] === undefined ? '' : source[key], placeholder: props.device === 'desktop' ? '' : 'ارث‌بری', onChange: function (event) { props.changeStyle(key, type === 'number' || !type ? Number(event.target.value) : event.target.value); } })); }
    if (!node) return h('aside', { className: 'woact-page-inspector' }, h('header', null, h('strong', null, 'تنظیمات صفحه')), h(Field, { label: 'عنوان برگه' }, h('input', { value: props.page.title, onChange: function (event) { props.changePage('title', event.target.value); } })), h(Field, { label: 'عرض محتوا' }, h('input', { type: 'number', value: props.document.settings.contentWidth, onChange: function (event) { props.changeSetting('contentWidth', Number(event.target.value)); } })), h(Field, { label: 'پس‌زمینه' }, h('input', { type: 'color', value: props.document.settings.background, onChange: function (event) { props.changeSetting('background', event.target.value); } })), h(Field, { label: 'فعال‌سازی صفحه React' }, h('input', { type: 'checkbox', checked: props.page.enabled, onChange: function (event) { props.changePage('enabled', event.target.checked); } })));
    return h('aside', { className: 'woact-page-inspector' }, h('header', null, h('strong', null, def ? def.label : node.type), h('code', null, node.id)), h('div', { className: 'woact-page-inspector-tabs' }, [['content', 'محتوا'], ['style', 'استایل'], ['advanced', 'پیشرفته']].map(function (item) { return h('button', { key: item[0], className: tab === item[0] ? 'is-active' : '', onClick: function () { setTab(item[0]); } }, item[1]); })),
      tab === 'content' ? h('div', null, (def.fields || []).map(function (field) { return h(Field, { key: field.id, label: field.label }, h(Control, { field: field, value: node.props[field.id], change: function (value) { props.changeProp(field.id, value); } })); }), h(Field, { label: 'داده پویا' }, h('select', { value: node.dynamic || '', onChange: function (event) { props.changeNode('dynamic', event.target.value); } }, (props.manifest.dynamicSources || []).map(function (item) { return h('option', { key: item.value, value: item.value }, item.label); })))) : null,
      tab === 'style' ? h('div', null, h('p', { className: 'woact-responsive-note' }, props.device === 'desktop' ? 'مقدار پایه' : 'مقادیر این دستگاه؛ خالی یعنی ارث‌بری'), styleField('فاصله بین عناصر', 'gap'), styleField('فاصله داخلی', 'padding'), styleField('فاصله بیرونی', 'margin'), styleField('حداقل ارتفاع', 'minHeight'), styleField('حداکثر عرض', 'maxWidth'), styleField('رنگ متن', 'color', 'color'), styleField('پس‌زمینه', 'background', 'color'), styleField('اندازه فونت', 'fontSize'), styleField('وزن فونت', 'fontWeight'), styleField('گردی', 'radius'), styleField('ضخامت حاشیه', 'borderWidth'), styleField('رنگ حاشیه', 'borderColor', 'color'), h(Field, { label: 'جهت' }, h('select', { value: (props.device === 'desktop' ? node.style : node.responsive[props.device]).direction || '', onChange: function (event) { props.changeStyle('direction', event.target.value); } }, h('option', { value: '' }, 'ارث‌بری'), h('option', { value: 'column' }, 'عمودی'), h('option', { value: 'row' }, 'افقی')))) : null,
      tab === 'advanced' ? h('div', null, h(Field, { label: 'قفل ویرایش' }, h('input', { type: 'checkbox', checked: !!node.locked, onChange: function (event) { props.changeNode('locked', event.target.checked); } })), h('h3', null, 'نمایش در دستگاه‌ها'), ['desktop', 'tablet', 'mobile'].map(function (device) { return h('label', { className: 'woact-check', key: device }, h('input', { type: 'checkbox', checked: node.visibility[device] !== false, onChange: function (event) { props.changeVisibility(device, event.target.checked); } }), device); }), styleField('Z-index', 'zIndex')) : null
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
    var undo = useRef([]), redo = useRef([]), clipboard = useRef(null);
    var importRef = useRef(null);
    var dirty = page && saved ? !same(page, saved) : false;
    useEffect(function () { apiFetch({ path: '/woact/v1/admin/pages' }).then(function (value) { setData(value); var requested = Number(config.initialPostId || 0); if (requested) load(requested); else if (value.pages[0]) load(value.pages[0].id); }).catch(function (error) { setNotice({ type: 'error', message: error.message || 'اطلاعات صفحه‌ساز بارگذاری نشد.' }); }); }, []);
    useEffect(function () { if (!dirty) return; var timer = setTimeout(function () { save(true); }, 12000); return function () { clearTimeout(timer); }; }, [page, dirty]);
    function load(id) { return apiFetch({ path: '/woact/v1/admin/pages/' + id }).then(function (value) { setPage(clone(value)); setSaved(clone(value)); setSelected(''); undo.current = []; redo.current = []; return value; }).catch(function (error) { setNotice({ type: 'error', message: error.message || 'این نوشته برای ویرایش با Woact بارگذاری نشد.' }); return null; }); }
    function commit(mutator) { if (!page) return; undo.current.push(clone(page)); if (undo.current.length > 60) undo.current.shift(); redo.current = []; var next = clone(page); mutator(next); setPage(next); }
    function history(direction) { var source = direction === 'undo' ? undo : redo, target = direction === 'undo' ? redo : undo; if (!source.current.length) return; target.current.push(clone(page)); setPage(source.current.pop()); }
    function create() { var title = window.prompt('عنوان صفحه جدید:', 'صفحه React جدید'); if (!title) return; apiFetch({ path: '/woact/v1/admin/pages', method: 'POST', data: { title: title } }).then(function (value) { setData(function (current) { return Object.assign({}, current, { pages: [{ id: value.id, title: value.title, status: value.status, enabled: true, hasDocument: true }].concat(current.pages) }); }); setPage(clone(value)); setSaved(clone(value)); }); }
    function save(silent) { if (!page || saving) return Promise.resolve(page); setSaving(true); return apiFetch({ path: '/woact/v1/admin/pages/' + page.id, method: 'POST', data: { title: page.title, enabled: page.enabled, document: page.draft, revision: page.revision } }).then(function (value) { setPage(clone(value)); setSaved(clone(value)); if (!silent) setNotice({ type: 'success', message: 'پیش‌نویس ذخیره شد.' }); return value; }).catch(function (error) { setNotice({ type: 'error', message: error.message }); throw error; }).finally(function () { setSaving(false); }); }
    function publish() { save(true).then(function (value) { return apiFetch({ path: '/woact/v1/admin/pages/' + value.id + '/publish', method: 'POST' }); }).then(function (value) { setPage(clone(value)); setSaved(clone(value)); setNotice({ type: 'success', message: 'صفحه React منتشر شد.' }); }); }
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
          'فرزندان فقط داخل section، container، columns و column قرار می‌گیرند.',
          'برای موبایل و تبلت responsive و visibility را حفظ کن و متن‌ها را فارسی بنویس.',
          'هیچ JavaScript، iframe ناشناس، shortcode ناشناس یا داده محرمانه تولید نکن.'
        ],
        userBrief: 'اینجا نوع سایت، رنگ‌ها، بخش‌ها، محتوا و سبک دلخواه را برای هوش مصنوعی بنویسید.',
        outputContract: {
          product: 'woact-page', schemaVersion: data.manifest.schemaVersion, version: data.version,
          title: 'عنوان صفحه', document: { schemaVersion: data.manifest.schemaVersion, settings: { contentWidth: 1180, background: '#ffffff', className: '', hideTitle: true }, nodes: [] }
        },
        nodeContract: {
          required: ['id', 'type', 'props', 'style', 'responsive', 'visibility', 'dynamic', 'locked', 'children'],
          containers: ['section', 'container', 'columns', 'column'],
          styleFields: ['display', 'direction', 'gap', 'padding', 'margin', 'background', 'color', 'width', 'maxWidth', 'minHeight', 'align', 'justify', 'fontSize', 'fontWeight', 'radius', 'borderWidth', 'borderColor', 'shadow', 'zIndex'],
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
    function drop(targetId, info) { commit(function (next) { var target = targetId ? walk(next.draft.nodes, targetId, null) : null, list = target && canContain(target.node) ? target.node.children : next.draft.nodes; if (info.action === 'new') list.push(makeNode(data.manifest, info.type)); if (info.action === 'move') { var source = walk(next.draft.nodes, info.id, null); if (!source || (target && contains(source.node, target.node.id))) return; var item = source.list.splice(source.index, 1)[0]; list.push(item); } }); }
    function remove(id) { var found = walk(page.draft.nodes, id, null); if (!found || found.node.locked) return; commit(function (next) { var item = walk(next.draft.nodes, id, null); item.list.splice(item.index, 1); }); setSelected(''); }
    function duplicate(id) { commit(function (next) { var found = walk(next.draft.nodes, id, null), copy = renewIds(clone(found.node)); found.list.splice(found.index + 1, 0, copy); }); }
    function changeNode(key, value) { commit(function (next) { walk(next.draft.nodes, selected, null).node[key] = value; }); }
    function changeProp(key, value) { commit(function (next) { walk(next.draft.nodes, selected, null).node.props[key] = value; }); }
    function changeStyle(key, value) { commit(function (next) { var node = walk(next.draft.nodes, selected, null).node; if (device === 'desktop') node.style[key] = value; else node.responsive[device][key] = value; }); }
    function changeVisibility(target, value) { commit(function (next) { walk(next.draft.nodes, selected, null).node.visibility[target] = value; }); }
    function changePage(key, value) { commit(function (next) { next[key] = value; }); }
    function changeSetting(key, value) { commit(function (next) { next.draft.settings[key] = value; }); }
    function keydown(event) { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') { event.preventDefault(); history(event.shiftKey ? 'redo' : 'undo'); } if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'c' && selected) { clipboard.current = clone(walk(page.draft.nodes, selected, null).node); } if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'v' && clipboard.current) { event.preventDefault(); commit(function (next) { var copy = renewIds(clone(clipboard.current)); var target = selected && walk(next.draft.nodes, selected, null); (target && canContain(target.node) ? target.node.children : next.draft.nodes).push(copy); }); } }
    useEffect(function () { window.addEventListener('keydown', keydown); return function () { window.removeEventListener('keydown', keydown); }; }, [page, selected, device]);
    if (!data) return h('div', { className: 'woact-page-loading' }, 'در حال بارگذاری صفحه‌ساز…');
    if (!page) return h('div', { className: 'woact-page-loading' }, h('span', { className: 'spinner is-active' }), h('p', null, notice ? notice.message : 'در حال بازکردن مستقیم محتوا در ویرایشگر Woact…'));
    var dynamicData = { site: config.site || {}, post: { title: page.title, excerpt: 'خلاصه نمونه صفحه', featuredImage: '' }, user: config.user || {}, widgets: config.widgetData || {} };
    return h('div', { className: 'woact-page-builder' }, h('header', { className: 'woact-page-topbar' }, h('div', { className: 'woact-page-brand' }, h('b', null, 'W'), h('span', null, h('strong', null, 'Woact Page Builder'), h('small', null, 'Stage 5 · ' + data.version))), h('div', { className: 'woact-current-document' }, h('strong', null, (page.postType === 'post' ? 'نوشته: ' : 'برگه: ') + page.title)), h('a', { href: page.editUrl, className: 'button' }, 'بازگشت به ویرایش وردپرس'), h(Devices, { value: device, change: setDevice }), h('div', { className: 'woact-page-actions' }, h('button', { disabled: !undo.current.length, onClick: function () { history('undo'); } }, '↶'), h('button', { disabled: !redo.current.length, onClick: function () { history('redo'); } }, '↷'), h('button', { onClick: exportJson }, 'JSON خروجی'), h('button', { onClick: exportAiJson, title: 'دانلود قرارداد، فهرست ویجت‌ها و سند فعلی برای طراحی با هوش مصنوعی' }, 'JSON برای هوش مصنوعی'), h('button', { onClick: function () { importRef.current.click(); } }, 'درون‌ریزی'), h('input', { ref: importRef, type: 'file', hidden: true, accept: '.json,application/json', onChange: importJson }), h('a', { href: page.viewUrl, target: '_blank', rel: 'noreferrer' }, 'مشاهده صفحه'), h('button', { disabled: !dirty || saving, onClick: function () { save(false); } }, saving ? 'ذخیره…' : 'ذخیره'), h('button', { className: 'is-primary', onClick: publish }, 'انتشار'))),
      h('div', { className: 'woact-page-status' }, h('span', { className: 'is-live' }, '● بوم زنده React'), h('span', { className: dirty ? 'is-dirty' : 'is-saved' }, dirty ? 'تغییرات ذخیره‌نشده' : 'ذخیره شده'), h('span', null, 'Ctrl+C / Ctrl+V / Ctrl+Z')),
      h(Notice, { value: notice, close: function () { setNotice(null); } }), h('div', { className: 'woact-page-body' }, h(Palette, { manifest: data.manifest, document: page.draft, add: add, select: setSelected }), h(Canvas, { manifest: data.manifest, document: page.draft, selected: selected, select: setSelected, device: device, data: dynamicData, add: add, drop: drop, remove: remove, duplicate: duplicate }), h(Inspector, { manifest: data.manifest, page: page, document: page.draft, selected: selected, device: device, changeNode: changeNode, changeProp: changeProp, changeStyle: changeStyle, changeVisibility: changeVisibility, changePage: changePage, changeSetting: changeSetting }))
    );
  }
  var root = document.getElementById('woact-page-builder-root');
  if (!root) return;
  if (e.createRoot) e.createRoot(root).render(h(App)); else e.render(h(App), root);
})(window.wp, window.WoactPageBuilderConfig || {});
