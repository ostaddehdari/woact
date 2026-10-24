(function (wp, config) {
  'use strict';
  if (!wp || !wp.element || !wp.apiFetch) return;
  var e = wp.element;
  var h = e.createElement;
  var Fragment = e.Fragment;
  var useEffect = e.useEffect;
  var useMemo = e.useMemo;
  var useRef = e.useRef;
  var useState = e.useState;
  var apiFetch = wp.apiFetch;
  if (config.nonce && apiFetch.createNonceMiddleware) apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));

  function clone(value) { return JSON.parse(JSON.stringify(value)); }
  function same(a, b) { return JSON.stringify(a) === JSON.stringify(b); }
  function uid(prefix) { return prefix + '_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8); }
  function defaultVisibility() { return { desktop: true, tablet: true, mobile: true }; }

  function Notice(props) {
    if (!props.value) return null;
    return h('div', { className: 'woact-builder-notice is-' + props.value.type }, h('span', null, props.value.message), h('button', { type: 'button', onClick: props.close }, '×'));
  }

  function Palette(props) {
    var _tab = useState('elements'), tab = _tab[0], setTab = _tab[1];
    var _query = useState(''), query = _query[0], setQuery = _query[1];
    var groups = props.manifest.categories || [];
    return h('aside', { className: 'woact-builder-palette' },
      h('div', { className: 'woact-builder-panel-tabs' },
        h('button', { type: 'button', className: tab === 'elements' ? 'is-active' : '', onClick: function () { setTab('elements'); } }, 'المان‌ها'),
        h('button', { type: 'button', className: tab === 'navigator' ? 'is-active' : '', onClick: function () { setTab('navigator'); } }, 'ناوبری')
      ),
      tab === 'elements' ? h(Fragment, null,
      h('div', { className: 'woact-builder-panel-title' }, h('strong', null, 'عناصر'), h('span', null, 'بکشید و رها کنید')),
      h('div', { className: 'woact-builder-search' }, h('span', { className: 'dashicons dashicons-search' }), h('input', { type: 'search', value: query, placeholder: 'جست‌وجوی المان…', onChange: function (event) { setQuery(event.target.value); } })),
      groups.map(function (group) {
        var normalized = query.trim().toLowerCase();
        var items = (props.manifest.elements || []).filter(function (item) { return item.category === group.id && (!normalized || item.label.toLowerCase().indexOf(normalized) !== -1 || item.type.indexOf(normalized) !== -1); });
        if (!items.length) return null;
        return h('section', { key: group.id }, h('h3', null, group.label), h('div', { className: 'woact-builder-elements' }, items.map(function (item) {
          return h('button', {
            type: 'button', key: item.type, draggable: true, className: 'woact-builder-element-card',
            onDragStart: function (event) { event.dataTransfer.setData('text/woact', JSON.stringify({ action: 'new', type: item.type })); },
            onClick: function () { props.quickAdd(item.type); },
          }, h('span', { className: 'dashicons dashicons-' + item.icon }), h('span', null, item.label));
        })));
      })) : h('div', { className: 'woact-navigator' }, (props.template.draft.rows || []).map(function (row, rowIndex) {
        return h('div', { className: 'woact-navigator__row', key: row.id },
          h('button', { type: 'button', onClick: function () { props.select({ kind: 'row', rowId: row.id }); } }, h('span', { className: 'dashicons dashicons-screenoptions' }), 'ردیف ' + (rowIndex + 1)),
          (row.columns || []).map(function (column, columnIndex) { return h('div', { className: 'woact-navigator__column', key: column.id },
            h('button', { type: 'button', onClick: function () { props.select({ kind: 'column', rowId: row.id, columnId: column.id }); } }, 'ستون ' + (columnIndex + 1)),
            (column.elements || []).map(function (item) { var def = (props.manifest.elements || []).find(function (entry) { return entry.type === item.type; }); return h('button', { type: 'button', className: 'woact-navigator__element', key: item.id, onClick: function () { props.select({ kind: 'element', rowId: row.id, columnId: column.id, elementId: item.id }); } }, def ? def.label : item.type); })
          ); })
        );
      }))
    );
  }

  function DeviceToolbar(props) {
    return h('div', { className: 'woact-device-toolbar' }, ['desktop', 'tablet', 'mobile'].map(function (device) {
      var labels = { desktop: 'دسکتاپ', tablet: 'تبلت', mobile: 'موبایل' };
      var icons = { desktop: 'desktop', tablet: 'tablet', mobile: 'smartphone' };
      return h('button', { type: 'button', key: device, className: props.device === device ? 'is-active' : '', onClick: function () { props.change(device); } }, h('span', { className: 'dashicons dashicons-' + icons[device] }), labels[device]);
    }));
  }

  function CanvasElement(props) {
    var item = props.element;
    var definition = props.definitions[item.type] || { label: item.type, icon: 'admin-generic' };
    var RuntimeElement = window.WoactLayoutRenderer && window.WoactLayoutRenderer.Element;
    return h('div', {
      className: 'woact-canvas-element ' + (props.selected ? 'is-selected ' : '') + (props.hiddenOnDevice ? 'is-device-hidden' : ''),
      draggable: true,
      onDragStart: function (event) { event.stopPropagation(); event.dataTransfer.setData('text/woact', JSON.stringify({ action: 'move', rowId: props.rowId, columnId: props.columnId, elementId: item.id })); },
      onClick: function (event) { event.stopPropagation(); props.select({ kind: 'element', rowId: props.rowId, columnId: props.columnId, elementId: item.id }); },
    },
    h('div', { className: 'woact-canvas-element__toolbar' },
      h('span', { className: 'dashicons dashicons-' + definition.icon }), h('strong', null, definition.label),
      h('div', { className: 'woact-canvas-element__actions' },
        h('button', { type: 'button', onClick: function (event) { event.stopPropagation(); props.duplicate(); }, title: 'تکثیر' }, '⧉'),
        h('button', { type: 'button', onClick: function (event) { event.stopPropagation(); props.remove(); }, title: 'حذف' }, '×')
      )
    ),
    h('div', { className: 'woact-canvas-element__live', onClick: function (event) { event.preventDefault(); } }, RuntimeElement ? h(RuntimeElement, { element: item, data: props.runtimeData }) : definition.label));
  }

  function Canvas(props) {
    var document = props.template.draft;
    var definitions = {};
    var ShapeDivider = window.WoactLayoutRenderer && window.WoactLayoutRenderer.ShapeDivider;
    (props.manifest.elements || []).forEach(function (item) { definitions[item.type] = item; });
    return h('div', { className: 'woact-builder-canvas-shell device-' + props.device },
      h('div', { className: 'woact-builder-canvas', onClick: function () { props.select({ kind: 'document' }); } },
        (document.rows || []).map(function (row, rowIndex) {
          var isRow = props.selected.kind === 'row' && props.selected.rowId === row.id;
          var rowVisible = !row.visibility || row.visibility[props.device] !== false;
          var rowStyle = row.style || {};
          return h('section', { className: 'woact-canvas-row ' + (isRow ? 'is-selected ' : '') + (rowVisible ? '' : 'is-device-hidden'), key: row.id, style: { background: rowStyle.background || '#fff', color: rowStyle.color || '#17202a', minHeight: (rowStyle.minHeight || 64) + 'px', '--woact-editor-gap': (rowStyle.gap || 20) + 'px' }, onClick: function (event) { event.stopPropagation(); props.select({ kind: 'row', rowId: row.id }); } },
            h('div', { className: 'woact-canvas-row__bar' },
              h('span', null, 'ردیف ' + (rowIndex + 1)),
              h('button', { type: 'button', onClick: function (event) { event.stopPropagation(); props.moveRow(row.id, -1); }, disabled: rowIndex === 0 }, '↑'),
              h('button', { type: 'button', onClick: function (event) { event.stopPropagation(); props.moveRow(row.id, 1); }, disabled: rowIndex === document.rows.length - 1 }, '↓'),
              h('button', { type: 'button', onClick: function (event) { event.stopPropagation(); props.removeRow(row.id); } }, 'حذف')
            ),
            ShapeDivider ? h(ShapeDivider, { shape: rowStyle.shapeTop, position: 'top' }) : null,
            h('div', { className: 'woact-canvas-columns ' + (rowStyle.width === 'full' ? 'is-full' : '') }, (row.columns || []).map(function (column) {
              var isColumn = props.selected.kind === 'column' && props.selected.columnId === column.id;
              return h('div', {
                key: column.id,
                className: 'woact-canvas-column ' + (isColumn ? 'is-selected' : ''),
                style: { gridColumn: 'span ' + (column.width || 12) },
                onClick: function (event) { event.stopPropagation(); props.select({ kind: 'column', rowId: row.id, columnId: column.id }); },
                onDragOver: function (event) { event.preventDefault(); event.currentTarget.classList.add('is-drop'); },
                onDragLeave: function (event) { event.currentTarget.classList.remove('is-drop'); },
                onDrop: function (event) { event.preventDefault(); event.stopPropagation(); event.currentTarget.classList.remove('is-drop'); try { props.drop(row.id, column.id, JSON.parse(event.dataTransfer.getData('text/woact'))); } catch (ignore) {} },
              },
              h('span', { className: 'woact-canvas-column__label' }, 'ستون ' + column.width + '/12'),
              (column.elements || []).map(function (item) {
                var itemVisible = !item.visibility || item.visibility[props.device] !== false;
                return h(CanvasElement, {
                  key: item.id, element: item, rowId: row.id, columnId: column.id, definitions: definitions,
                  selected: props.selected.kind === 'element' && props.selected.elementId === item.id,
                  hiddenOnDevice: !itemVisible,
                  runtimeData: props.runtimeData,
                  select: props.select,
                  remove: function () { props.removeElement(row.id, column.id, item.id); },
                  duplicate: function () { props.duplicateElement(row.id, column.id, item.id); },
                });
              }),
              !column.elements.length ? h('div', { className: 'woact-canvas-drop-hint' }, 'عنصر را اینجا رها کنید') : null
              );
            })),
            ShapeDivider ? h(ShapeDivider, { shape: rowStyle.shapeBottom, position: 'bottom' }) : null
          );
        }),
        h('div', { className: 'woact-add-row' },
          h('span', null, 'افزودن ردیف:'),
          [1, 2, 3, 4].map(function (count) { return h('button', { type: 'button', key: count, onClick: function (event) { event.stopPropagation(); props.addRow(count); } }, count + ' ستون'); })
        )
      )
    );
  }

  function Control(props) {
    var field = props.field;
    var value = props.value;
    if (field.type === 'switch') return h('label', { className: 'woact-mini-switch' }, h('input', { type: 'checkbox', checked: !!value, onChange: function (event) { props.change(event.target.checked); } }), h('span', null, value ? 'فعال' : 'غیرفعال'));
    if (field.type === 'textarea') return h('textarea', { rows: 5, value: value || '', onChange: function (event) { props.change(event.target.value); } });
    if (field.type === 'color') return h('div', { className: 'woact-inspector-color' }, h('input', { type: 'color', value: value || '#ffffff', onChange: function (event) { props.change(event.target.value); } }), h('input', { type: 'text', dir: 'ltr', value: value || '', onChange: function (event) { props.change(event.target.value); } }));
    if (field.type === 'number') return h('input', { type: 'number', value: value || 0, onChange: function (event) { props.change(Number(event.target.value)); } });
    if (field.type === 'select') {
      var options = field.options || (field.id === 'location' ? [{ value: 'primary', label: 'منوی اصلی' }, { value: 'footer', label: 'منوی فوتر' }] : field.id === 'variant' ? [{ value: 'primary', label: 'اصلی' }, { value: 'outline', label: 'خطی' }] : []);
      return h('select', { value: value || field.default || '', onChange: function (event) { props.change(event.target.value); } }, options.map(function (option) { return h('option', { key: option.value, value: option.value }, option.label); }));
    }
    return h('input', { type: field.type === 'url' ? 'url' : 'text', dir: field.type === 'url' ? 'ltr' : 'auto', value: value || '', onChange: function (event) { props.change(event.target.value); } });
  }

  function Visibility(props) {
    var value = props.value || defaultVisibility();
    return h('div', { className: 'woact-visibility' }, ['desktop', 'tablet', 'mobile'].map(function (device) {
      var labels = { desktop: 'دسکتاپ', tablet: 'تبلت', mobile: 'موبایل' };
      return h('label', { key: device }, h('input', { type: 'checkbox', checked: value[device] !== false, onChange: function (event) { var next = Object.assign({}, value); next[device] = event.target.checked; props.change(next); } }), labels[device]);
    }));
  }

  function InspectorTabs(props) {
    return h('div', { className: 'woact-inspector-tabs' }, [['content', 'محتوا', 'edit'], ['style', 'استایل', 'admin-customizer'], ['advanced', 'پیشرفته', 'admin-generic']].map(function (item) {
      return h('button', { type: 'button', key: item[0], className: props.value === item[0] ? 'is-active' : '', onClick: function () { props.change(item[0]); } }, h('span', { className: 'dashicons dashicons-' + item[2] }), item[1]);
    }));
  }

  function ShapeControls(props) {
    var shape = props.value || { type: 'none', color: '#ffffff', height: 40, flip: false, invert: false };
    function change(key, value) { props.change(Object.assign({}, shape, (function () { var out = {}; out[key] = value; return out; })())); }
    return h('div', { className: 'woact-shape-controls' },
      h('label', { className: 'woact-inspector-field' }, h('span', null, 'طرح'), h('select', { value: shape.type || 'none', onChange: function (event) { change('type', event.target.value); } }, (props.shapes || []).map(function (item) { return h('option', { key: item.value, value: item.value }, item.label); }))),
      h('label', { className: 'woact-inspector-field' }, h('span', null, 'رنگ'), h(Control, { field: { type: 'color' }, value: shape.color, change: function (value) { change('color', value); } })),
      h('label', { className: 'woact-inspector-field' }, h('span', null, 'ارتفاع'), h('input', { type: 'range', min: 10, max: 240, value: shape.height || 40, onChange: function (event) { change('height', Number(event.target.value)); } }), h('small', null, (shape.height || 40) + 'px')),
      h('div', { className: 'woact-shape-toggles' }, h('label', null, h('input', { type: 'checkbox', checked: !!shape.flip, onChange: function (event) { change('flip', event.target.checked); } }), ' قرینه'), h('label', null, h('input', { type: 'checkbox', checked: !!shape.invert, onChange: function (event) { change('invert', event.target.checked); } }), ' وارونه'))
    );
  }

  function Inspector(props) {
    var _tab = useState('content'), tab = _tab[0], setTab = _tab[1];
    var selected = props.selected;
    var document = props.template.draft;
    var row = (document.rows || []).find(function (item) { return item.id === selected.rowId; });
    var column = row && (row.columns || []).find(function (item) { return item.id === selected.columnId; });
    var element = column && (column.elements || []).find(function (item) { return item.id === selected.elementId; });
    var definition = element && (props.manifest.elements || []).find(function (item) { return item.type === element.type; });
    function field(label, control) { return h('label', { className: 'woact-inspector-field' }, h('span', null, label), control); }
    if (element) {
      return h('aside', { className: 'woact-builder-inspector' }, h('div', { className: 'woact-builder-panel-title' }, h('strong', null, definition ? definition.label : element.type), h('code', null, element.id)),
        h(InspectorTabs, { value: tab, change: setTab }),
        tab === 'content' ? h('div', { className: 'woact-inspector-panel' }, (definition && definition.fields || []).map(function (item) { return field(item.label, h(Control, { key: item.id, field: item, value: element.props[item.id], change: function (value) { props.changeElement('props', item.id, value); } })); })) : null,
        tab === 'style' ? h('div', { className: 'woact-inspector-panel' }, h('h3', null, 'استایل'),
          field('رنگ متن', h(Control, { field: { type: 'color' }, value: element.style.color, change: function (value) { props.changeElement('style', 'color', value); } })),
          field('پس‌زمینه', h(Control, { field: { type: 'color' }, value: element.style.background, change: function (value) { props.changeElement('style', 'background', value); } })),
          field('اندازه فونت', h(Control, { field: { type: 'number' }, value: element.style.fontSize, change: function (value) { props.changeElement('style', 'fontSize', value); } })),
          field('فاصله داخلی', h(Control, { field: { type: 'number' }, value: element.style.padding, change: function (value) { props.changeElement('style', 'padding', value); } })),
          field('گردی', h(Control, { field: { type: 'number' }, value: element.style.radius, change: function (value) { props.changeElement('style', 'radius', value); } }))) : null,
        tab === 'advanced' ? h('div', { className: 'woact-inspector-panel' }, h('h3', null, 'نمایش واکنش‌گرا'), h(Visibility, { value: element.visibility, change: function (value) { props.changeElement('visibility', null, value); } })) : null
      );
    }
    if (column) {
      return h('aside', { className: 'woact-builder-inspector' }, h('div', { className: 'woact-builder-panel-title' }, h('strong', null, 'تنظیمات ستون')),
        field('عرض از ۱۲', h('input', { type: 'range', min: 1, max: 12, value: column.width, onChange: function (event) { props.changeColumn('width', Number(event.target.value)); } })),
        field('چیدمان', h('select', { value: column.align, onChange: function (event) { props.changeColumn('align', event.target.value); } }, [['start', 'ابتدا'], ['center', 'وسط'], ['end', 'انتها'], ['between', 'فاصله بین عناصر']].map(function (item) { return h('option', { key: item[0], value: item[0] }, item[1]); })))
      );
    }
    if (row) {
      var rs = row.style || {};
      return h('aside', { className: 'woact-builder-inspector' }, h('div', { className: 'woact-builder-panel-title' }, h('strong', null, 'تنظیمات ردیف')),
        h(InspectorTabs, { value: tab, change: setTab }),
        tab === 'content' ? h('div', { className: 'woact-inspector-panel' },
        field('عرض', h('select', { value: rs.width, onChange: function (event) { props.changeRow('width', event.target.value); } }, h('option', { value: 'contained' }, 'داخل کانتینر'), h('option', { value: 'full' }, 'تمام عرض'))),
        field('حداقل ارتفاع', h(Control, { field: { type: 'number' }, value: rs.minHeight, change: function (value) { props.changeRow('minHeight', value); } })),
        field('فاصله عناصر', h(Control, { field: { type: 'number' }, value: rs.gap, change: function (value) { props.changeRow('gap', value); } }))) : null,
        tab === 'style' ? h('div', { className: 'woact-inspector-panel' },
        field('پس‌زمینه', h(Control, { field: { type: 'color' }, value: rs.background, change: function (value) { props.changeRow('background', value); } })),
        field('رنگ متن', h(Control, { field: { type: 'color' }, value: rs.color, change: function (value) { props.changeRow('color', value); } })),
        h('details', { className: 'woact-shape-section', open: true }, h('summary', null, 'جداکنندهٔ بالای ردیف'), h(ShapeControls, { value: rs.shapeTop, shapes: props.manifest.shapes, change: function (value) { props.changeRow('shapeTop', value); } })),
        h('details', { className: 'woact-shape-section' }, h('summary', null, 'جداکنندهٔ پایین ردیف'), h(ShapeControls, { value: rs.shapeBottom, shapes: props.manifest.shapes, change: function (value) { props.changeRow('shapeBottom', value); } }))) : null,
        tab === 'advanced' ? h('div', { className: 'woact-inspector-panel' },
        field('چسبان', h(Control, { field: { type: 'switch' }, value: rs.sticky, change: function (value) { props.changeRow('sticky', value); } })),
        field('هم‌پوشان', h(Control, { field: { type: 'switch' }, value: rs.overlay, change: function (value) { props.changeRow('overlay', value); } })),
        h('h3', null, 'نمایش واکنش‌گرا'), h(Visibility, { value: row.visibility, change: props.changeRowVisibility })) : null
      );
    }
    return h('aside', { className: 'woact-builder-inspector' }, h('div', { className: 'woact-builder-panel-title' }, h('strong', null, 'تنظیمات سند')),
      field('عنوان قالب', h('input', { type: 'text', value: props.template.title, onChange: function (event) { props.changeTemplate('title', event.target.value); } })),
      field('اولویت', h('input', { type: 'number', min: 0, max: 1000, value: props.template.priority, onChange: function (event) { props.changeTemplate('priority', Number(event.target.value)); } })),
      field('فعال', h(Control, { field: { type: 'switch' }, value: props.template.active, change: function (value) { props.changeTemplate('active', value); } })),
      field('هدر چسبان', h(Control, { field: { type: 'switch' }, value: document.settings.sticky, change: function (value) { props.changeDocumentSetting('sticky', value); } })),
      field('کوچک‌شدن با اسکرول', h(Control, { field: { type: 'switch' }, value: document.settings.shrinkOnScroll, change: function (value) { props.changeDocumentSetting('shrinkOnScroll', value); } })),
      h('h3', null, 'شروط نمایش'),
      (props.template.conditions || []).map(function (condition, index) {
        return h('div', { className: 'woact-condition', key: index },
          h('select', { value: condition.exclude ? 'exclude' : 'include', onChange: function (event) { props.changeCondition(index, 'exclude', event.target.value === 'exclude'); } }, h('option', { value: 'include' }, 'نمایش در'), h('option', { value: 'exclude' }, 'عدم نمایش در')),
          h('select', { value: condition.scope, onChange: function (event) { props.changeCondition(index, 'scope', event.target.value); } }, [['entire_site', 'کل سایت'], ['front_page', 'صفحه اصلی'], ['singular', 'صفحات تکی'], ['archive', 'آرشیوها'], ['post_type', 'نوع محتوا'], ['logged_in', 'کاربر واردشده'], ['logged_out', 'مهمان']].map(function (item) { return h('option', { key: item[0], value: item[0] }, item[1]); })),
          condition.scope === 'post_type' ? h('select', { value: condition.value || '', onChange: function (event) { props.changeCondition(index, 'value', event.target.value); } }, h('option', { value: '' }, 'انتخاب…'), (props.postTypes || []).map(function (item) { return h('option', { key: item.value, value: item.value }, item.label); })) : null,
          h('button', { type: 'button', onClick: function () { props.removeCondition(index); } }, '×')
        );
      }),
      h('button', { type: 'button', className: 'button', onClick: props.addCondition }, '+ شرط جدید')
    );
  }

  function PreviewDialog(props) {
    if (!props.open) return null;
    return h('div', { className: 'woact-builder-dialog-backdrop' }, h('div', { className: 'woact-builder-preview-dialog' },
      h('header', null, h('strong', null, 'پیش‌نمایش واقعی'), h('button', { type: 'button', onClick: props.close }, '×')),
      h('iframe', { title: 'پیش‌نمایش قالب', src: props.url })
    ));
  }

  function HistoryDialog(props) {
    if (!props.open) return null;
    return h('div', { className: 'woact-builder-dialog-backdrop' }, h('div', { className: 'woact-builder-history-dialog' },
      h('header', null, h('strong', null, 'تاریخچه انتشار'), h('button', { type: 'button', onClick: props.close }, '×')),
      props.revisions.length ? h('ul', null, props.revisions.map(function (revision) { return h('li', { key: revision.id }, h('span', null, new Date(revision.createdAt * 1000).toLocaleString('fa-IR') + ' — ' + revision.user), h('button', { type: 'button', className: 'button', onClick: function () { props.restore(revision.id); } }, 'بازگردانی')); })) : h('p', null, 'هنوز نسخه‌ای وجود ندارد.')
    ));
  }

  function LayoutList(props) {
    var _query = useState(''), query = _query[0], setQuery = _query[1];
    var _type = useState('all'), type = _type[0], setType = _type[1];
    var _status = useState('all'), status = _status[0], setStatus = _status[1];
    var _sort = useState('modified'), sort = _sort[0], setSort = _sort[1];
    var templates = (props.templates || []).filter(function (item) {
      return (type === 'all' || item.type === type) && (status === 'all' || (status === 'active' ? item.active : !item.active)) && (!query || String(item.title || '').toLowerCase().indexOf(query.toLowerCase()) !== -1);
    }).slice().sort(function (a, b) { return sort === 'title' ? String(a.title).localeCompare(String(b.title), 'fa') : Number(b.updatedAt || b.modifiedAt || 0) - Number(a.updatedAt || a.modifiedAt || 0); });
    return h('div', { className: 'woact-layout-library', dir: 'rtl' },
      h('header', { className: 'woact-layout-library__header' }, h('div', null, h('h1', null, 'هدر و فوترهای Woact'), h('p', null, 'قالب‌ها را جست‌وجو، فیلتر و مدیریت کنید؛ سپس وارد ویرایشگر زنده شوید.')), h('div', null, h('button', { className: 'button button-primary', onClick: function () { props.create('header'); } }, '+ هدر جدید'), h('button', { className: 'button', onClick: function () { props.create('footer'); } }, '+ فوتر جدید'))),
      h('div', { className: 'woact-layout-library__ai' }, h('strong', null, 'طراحی با هوش مصنوعی'), h('span', null, 'قرارداد کامل، همه المان‌ها، تنظیمات و SVGها را دریافت کنید.'), h('button', { className: 'button', onClick: props.aiJson }, 'JSON کامل برای هوش مصنوعی'), h('a', { className: 'button', href: props.sampleUrl, download: 'woact-ai-header-footer-example.json' }, 'JSON نمونه'), h('button', { className: 'button', onClick: props.importClick }, 'درون‌ریزی JSON')),
      h('div', { className: 'woact-layout-library__filters' }, h('input', { type: 'search', value: query, placeholder: 'جست‌وجوی عنوان…', onChange: function (event) { setQuery(event.target.value); } }), h('select', { value: type, onChange: function (event) { setType(event.target.value); } }, h('option', { value: 'all' }, 'همه نوع‌ها'), h('option', { value: 'header' }, 'هدرها'), h('option', { value: 'footer' }, 'فوترها')), h('select', { value: status, onChange: function (event) { setStatus(event.target.value); } }, h('option', { value: 'all' }, 'همه وضعیت‌ها'), h('option', { value: 'active' }, 'فعال'), h('option', { value: 'inactive' }, 'غیرفعال')), h('select', { value: sort, onChange: function (event) { setSort(event.target.value); } }, h('option', { value: 'modified' }, 'آخرین ویرایش'), h('option', { value: 'title' }, 'عنوان'))),
      templates.length ? h('div', { className: 'woact-layout-library__table' }, h('table', { className: 'widefat striped' }, h('thead', null, h('tr', null, h('th', null, 'عنوان'), h('th', null, 'نوع'), h('th', null, 'وضعیت'), h('th', null, 'اولویت'), h('th', null, 'عملیات'))), h('tbody', null, templates.map(function (item) { return h('tr', { key: item.id }, h('td', null, h('strong', null, item.title)), h('td', null, item.type === 'header' ? 'هدر' : 'فوتر'), h('td', null, h('span', { className: item.active ? 'woact-status-active' : 'woact-status-draft' }, item.active ? 'فعال' : 'غیرفعال')), h('td', null, item.priority), h('td', null, h('a', { className: 'button button-primary', href: config.editorUrl + encodeURIComponent(item.id) }, 'ویرایش با Woact'))); })))) : h('div', { className: 'woact-layout-library__empty' }, 'قالبی با این فیلتر پیدا نشد.'),
      h('input', { type: 'file', hidden: true, ref: props.importRef, accept: '.json,application/json', onChange: props.importTemplate })
    );
  }

  function App() {
    var _payload = useState(null), payload = _payload[0], setPayload = _payload[1];
    var _active = useState(''), active = _active[0], setActive = _active[1];
    var _working = useState(null), working = _working[0], setWorking = _working[1];
    var _saved = useState(null), saved = _saved[0], setSaved = _saved[1];
    var _selected = useState({ kind: 'document' }), selected = _selected[0], setSelected = _selected[1];
    var _device = useState('desktop'), device = _device[0], setDevice = _device[1];
    var _notice = useState(null), notice = _notice[0], setNotice = _notice[1];
    var _saving = useState(false), saving = _saving[0], setSaving = _saving[1];
    var _preview = useState(''), preview = _preview[0], setPreview = _preview[1];
    var _history = useState(false), history = _history[0], setHistory = _history[1];
    var importRef = useRef(null);
    var undoRef = useRef([]);
    var redoRef = useRef([]);
    var dirty = working && saved ? !same(working, saved) : false;

    useEffect(function () {
      apiFetch({ path: '/woact/v1/admin/layouts' }).then(function (data) {
        setPayload(data);
        if (config.mode === 'editor') { var requested = (data.templates || []).find(function (item) { return item.id === config.templateId; }); if (requested) loadTemplate(requested); }
      }).catch(function (error) { setNotice({ type: 'error', message: error.message || 'بارگذاری سازنده ناموفق بود.' }); });
    }, []);

    useEffect(function () {
      if (!working || !dirty) return undefined;
      var timer = window.setTimeout(function () { saveDraft(true); }, 1800);
      return function () { window.clearTimeout(timer); };
    }, [working]);

    useEffect(function () {
      function warn(event) { if (dirty) { event.preventDefault(); event.returnValue = ''; } }
      window.addEventListener('beforeunload', warn);
      return function () { window.removeEventListener('beforeunload', warn); };
    }, [dirty]);

    function loadTemplate(template) {
      setActive(template.id); setWorking(clone(template)); setSaved(clone(template)); setSelected({ kind: 'document' }); undoRef.current = []; redoRef.current = [];
    }
    function commit(mutator) {
      setWorking(function (current) {
        undoRef.current.push(clone(current)); if (undoRef.current.length > 60) undoRef.current.shift(); redoRef.current = [];
        var next = clone(current); mutator(next); return next;
      });
    }
    function undo() { if (!undoRef.current.length) return; redoRef.current.push(clone(working)); var next = undoRef.current.pop(); setWorking(next); }
    function redo() { if (!redoRef.current.length) return; undoRef.current.push(clone(working)); var next = redoRef.current.pop(); setWorking(next); }
    function replaceTemplate(template) {
      setPayload(function (current) { return Object.assign({}, current, { templates: current.templates.map(function (item) { return item.id === template.id ? template : item; }) }); });
      setWorking(clone(template)); setSaved(clone(template));
    }
    function saveDraft(silent) {
      if (!working || saving || !dirty) return Promise.resolve(working);
      var snapshot = clone(working);
      setSaving(true);
      return apiFetch({ path: '/woact/v1/admin/layouts/' + snapshot.id, method: 'POST', data: { title: snapshot.title, active: snapshot.active, priority: snapshot.priority, conditions: snapshot.conditions, document: snapshot.draft, revision: saved.revision } }).then(function (data) {
        setPayload(function (current) { return Object.assign({}, current, { templates: current.templates.map(function (item) { return item.id === data.id ? data : item; }) }); });
        setSaved(clone(data));
        setWorking(function (current) { return same(current, snapshot) ? clone(data) : Object.assign({}, current, { revision: data.revision }); });
        if (!silent) setNotice({ type: 'success', message: 'پیش‌نویس ذخیره شد.' }); return data;
      }).catch(function (error) { setNotice({ type: 'error', message: error.message || 'ذخیره ناموفق بود.' }); throw error; }).finally(function () { setSaving(false); });
    }
    function publish() {
      saveDraft(true).then(function (template) { return apiFetch({ path: '/woact/v1/admin/layouts/' + template.id + '/publish', method: 'POST' }); }).then(function (data) { replaceTemplate(data); setNotice({ type: 'success', message: 'قالب منتشر شد و اکنون در سایت فعال است.' }); });
    }
    function create(type) {
      apiFetch({ path: '/woact/v1/admin/layouts', method: 'POST', data: { type: type, title: type === 'header' ? 'هدر جدید' : 'فوتر جدید' } }).then(function (data) { if (config.mode === 'list') { window.location.href = config.editorUrl + encodeURIComponent(data.id); return; } setPayload(function (current) { return Object.assign({}, current, { templates: current.templates.concat([data]) }); }); loadTemplate(data); });
    }
    function duplicate() { apiFetch({ path: '/woact/v1/admin/layouts/' + working.id + '/duplicate', method: 'POST' }).then(function (data) { setPayload(function (current) { return Object.assign({}, current, { templates: current.templates.concat([data]) }); }); loadTemplate(data); }); }
    function removeTemplate() {
      if (!window.confirm('این قالب حذف شود؟')) return;
      apiFetch({ path: '/woact/v1/admin/layouts/' + working.id, method: 'DELETE' }).then(function () { var next = payload.templates.filter(function (item) { return item.id !== working.id; }); setPayload(Object.assign({}, payload, { templates: next })); if (next[0]) loadTemplate(next[0]); else { setWorking(null); setSaved(null); } });
    }
    function exportTemplate() {
      var doc = { product: 'woact-layout', schemaVersion: payload.schemaVersion, version: payload.version, exportedAt: new Date().toISOString(), template: { type: working.type, title: working.title, priority: working.priority, conditions: working.conditions, document: working.draft } };
      var url = URL.createObjectURL(new Blob([JSON.stringify(doc, null, 2)], { type: 'application/json' })); var link = document.createElement('a'); link.href = url; link.download = 'woact-' + working.type + '-' + Date.now() + '.json'; link.click(); URL.revokeObjectURL(url);
    }
    function importTemplate(event) {
      var file = event.target.files && event.target.files[0]; event.target.value = ''; if (!file) return;
      file.text().then(JSON.parse).then(function (doc) { return apiFetch({ path: '/woact/v1/admin/layouts/import', method: 'POST', data: { document: doc } }); }).then(function (data) { var imported = data.templates || []; setPayload(function (current) { return Object.assign({}, current, { templates: current.templates.concat(imported) }); }); if (imported[0]) loadTemplate(imported[0]); setNotice({ type: 'success', message: imported.length + ' قالب با موفقیت درون‌ریزی شد.' }); }).catch(function (error) { setNotice({ type: 'error', message: error.message || 'فایل معتبر نیست.' }); });
    }
    function copyAiPrompt() {
      var prompt = 'یک فایل JSON معتبر برای Woact Layout Bundle با schemaVersion برابر 2 بساز. خروجی فقط JSON باشد و شامل آرایه templates با دقیقاً یک header و یک footer باشد. هر template شامل type، title، priority، conditions و document است. فقط از element typeهای زیر استفاده کن: ' + payload.manifest.elements.map(function (item) { return item.type; }).join(', ') + '. فقط از shape typeهای زیر استفاده کن: ' + payload.manifest.shapes.map(function (item) { return item.value; }).join(', ') + '. ساختار دقیق را از فایل نمونه‌ای که همراه این پیام می‌فرستم حفظ کن؛ شناسه‌ها یکتا و انگلیسی باشند.';
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(prompt).then(function () { setNotice({ type: 'success', message: 'پرامپت هوش مصنوعی کپی شد.' }); });
      else window.prompt('این متن را کپی کنید:', prompt);
    }
    function exportAiJson() {
      var doc = { product: 'woact-layout-ai-request', schemaVersion: payload.schemaVersion, version: payload.version, language: 'fa-IR', direction: 'rtl', aiInstructions: ['یک هدر یا فوتر حرفه‌ای و واکنش‌گرا طراحی کن.', 'خروجی فقط JSON معتبر و بدون Markdown باشد.', 'فقط از elementها و shapeهای manifest استفاده کن.', 'شناسه‌ها یکتا و انگلیسی باشند.', 'خروجی نهایی باید product برابر woact-layout و دارای template یا templates باشد.'], outputContract: { product: 'woact-layout', schemaVersion: payload.schemaVersion, version: payload.version, templates: [{ type: 'header', title: 'هدر ساخته‌شده با AI', priority: 10, conditions: [{ scope: 'entire_site', value: '', exclude: false }], document: { schemaVersion: payload.schemaVersion, settings: { sticky: false, shrinkOnScroll: false }, rows: [] } }] }, manifest: payload.manifest, currentTemplates: payload.templates.map(function (item) { return { type: item.type, title: item.title, priority: item.priority, conditions: item.conditions, document: item.draft }; }) };
      var content = JSON.stringify(doc, null, 2); var url = URL.createObjectURL(new Blob([content], { type: 'application/json' })); var link = document.createElement('a'); link.href = url; link.download = 'woact-ai-layout-request.json'; link.click(); URL.revokeObjectURL(url); if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(content); setNotice({ type: 'success', message: 'JSON کامل هوش مصنوعی دانلود و کپی شد.' });
    }
    function openPreview() { saveDraft(true).then(function (template) { setPreview(payload.previewUrl + '&id=' + encodeURIComponent(template.id) + '&t=' + Date.now()); }); }
    function restore(revisionId) { if (!window.confirm('نسخه انتخاب‌شده بازگردانی شود؟')) return; apiFetch({ path: '/woact/v1/admin/layouts/' + working.id + '/restore', method: 'POST', data: { revisionId: revisionId } }).then(function (data) { replaceTemplate(data); setHistory(false); setNotice({ type: 'success', message: 'نسخه بازگردانی شد.' }); }); }

    function findColumn(doc, rowId, columnId) { var row = doc.rows.find(function (item) { return item.id === rowId; }); return row && row.columns.find(function (item) { return item.id === columnId; }); }
    function makeElement(type) { var def = payload.manifest.elements.find(function (item) { return item.type === type; }); return { id: uid('element'), type: type, props: clone(def ? def.defaults : {}), style: { color: '', background: '', fontSize: 0, padding: 0, radius: 0 }, visibility: defaultVisibility() }; }
    function quickAdd(type) { if (!working.draft.rows.length) return; var row = working.draft.rows[0]; var col = row.columns[0]; commit(function (next) { findColumn(next.draft, row.id, col.id).elements.push(makeElement(type)); }); }
    function drop(rowId, columnId, info) {
      commit(function (next) {
        var target = findColumn(next.draft, rowId, columnId); if (!target) return;
        if (info.action === 'new') target.elements.push(makeElement(info.type));
        if (info.action === 'move') { var source = findColumn(next.draft, info.rowId, info.columnId); if (!source) return; var index = source.elements.findIndex(function (item) { return item.id === info.elementId; }); if (index < 0) return; var item = source.elements.splice(index, 1)[0]; target.elements.push(item); }
      });
    }
    function addRow(count) { commit(function (next) { var width = Math.floor(12 / count); next.draft.rows.push({ id: uid('row'), columns: Array.from({ length: count }, function () { return { id: uid('column'), width: width, align: 'start', elements: [] }; }), style: { width: 'contained', minHeight: 64, gap: 20, background: '#ffffff', color: '#17202a', sticky: false, overlay: false, shapeTop: { type: 'none', color: '#ffffff', height: 40, flip: false, invert: false }, shapeBottom: { type: 'none', color: '#ffffff', height: 40, flip: false, invert: false } }, visibility: defaultVisibility() }); }); }
    function removeRow(id) { if (working.draft.rows.length < 2) return setNotice({ type: 'warning', message: 'حداقل یک ردیف باید باقی بماند.' }); commit(function (next) { next.draft.rows = next.draft.rows.filter(function (item) { return item.id !== id; }); }); setSelected({ kind: 'document' }); }
    function moveRow(id, direction) { commit(function (next) { var index = next.draft.rows.findIndex(function (item) { return item.id === id; }); var target = index + direction; if (index < 0 || target < 0 || target >= next.draft.rows.length) return; var row = next.draft.rows[index]; next.draft.rows[index] = next.draft.rows[target]; next.draft.rows[target] = row; }); }
    function removeElement(rowId, columnId, id) { commit(function (next) { var col = findColumn(next.draft, rowId, columnId); col.elements = col.elements.filter(function (item) { return item.id !== id; }); }); setSelected({ kind: 'column', rowId: rowId, columnId: columnId }); }
    function duplicateElement(rowId, columnId, id) { commit(function (next) { var col = findColumn(next.draft, rowId, columnId); var index = col.elements.findIndex(function (item) { return item.id === id; }); var copy = clone(col.elements[index]); copy.id = uid('element'); col.elements.splice(index + 1, 0, copy); }); }
    function changeElement(section, key, value) { commit(function (next) { var col = findColumn(next.draft, selected.rowId, selected.columnId); var item = col.elements.find(function (element) { return element.id === selected.elementId; }); if (section === 'visibility') item.visibility = value; else item[section][key] = value; }); }
    function changeColumn(key, value) { commit(function (next) { findColumn(next.draft, selected.rowId, selected.columnId)[key] = value; }); }
    function changeRow(key, value) { commit(function (next) { next.draft.rows.find(function (item) { return item.id === selected.rowId; }).style[key] = value; }); }
    function changeRowVisibility(value) { commit(function (next) { next.draft.rows.find(function (item) { return item.id === selected.rowId; }).visibility = value; }); }
    function changeTemplate(key, value) { commit(function (next) { next[key] = value; }); }
    function changeDocumentSetting(key, value) { commit(function (next) { next.draft.settings[key] = value; }); }
    function changeCondition(index, key, value) { commit(function (next) { next.conditions[index][key] = value; if (key === 'scope' && value !== 'post_type') next.conditions[index].value = ''; }); }
    function addCondition() { commit(function (next) { next.conditions.push({ scope: 'entire_site', value: '', exclude: false }); }); }
    function removeCondition(index) { commit(function (next) { next.conditions.splice(index, 1); if (!next.conditions.length) next.conditions.push({ scope: 'entire_site', value: '', exclude: false }); }); }

    var definitions = useMemo(function () { var out = {}; if (payload) payload.manifest.elements.forEach(function (item) { out[item.type] = item; }); return out; }, [payload]);
    if (!payload) return h('div', { className: 'woact-builder-loading' }, h('span', { className: 'spinner is-active' }), h('p', null, 'در حال بارگذاری…'));
    if (config.mode === 'list') return h(Fragment, null, h(Notice, { value: notice, close: function () { setNotice(null); } }), h(LayoutList, { templates: payload.templates, create: create, aiJson: exportAiJson, sampleUrl: payload.sampleUrl, importRef: importRef, importClick: function () { importRef.current.click(); }, importTemplate: importTemplate }));
    if (!working) return h('div', { className: 'woact-builder-empty' }, h('h1', null, 'هدر و فوتر ساز Woact'), h('button', { className: 'button button-primary', onClick: function () { create('header'); } }, 'ساخت هدر'), h('button', { className: 'button', onClick: function () { create('footer'); } }, 'ساخت فوتر'));

    return h(Fragment, null,
      h('div', { className: 'woact-builder-app' },
        h('header', { className: 'woact-builder-topbar' },
          h('div', { className: 'woact-builder-brand' }, h('span', null, 'W'), h('div', null, h('strong', null, 'Woact Builder'), h('small', null, 'Stage 3 · ' + payload.version))),
          h('select', { value: active, onChange: function (event) { var target = payload.templates.find(function (item) { return item.id === event.target.value; }); if (!dirty || window.confirm('تغییرات ذخیره‌نشده نادیده گرفته شود؟')) loadTemplate(target); } }, payload.templates.map(function (item) { return h('option', { key: item.id, value: item.id }, (item.type === 'header' ? 'هدر: ' : 'فوتر: ') + item.title); })),
          h('div', { className: 'woact-builder-topbar__create' }, h('button', { type: 'button', onClick: function () { create('header'); } }, '+ هدر'), h('button', { type: 'button', onClick: function () { create('footer'); } }, '+ فوتر')),
          h(DeviceToolbar, { device: device, change: setDevice }),
          h('div', { className: 'woact-builder-topbar__actions' },
            h('button', { type: 'button', disabled: !undoRef.current.length, onClick: undo, title: 'واگرد' }, '↶'),
            h('button', { type: 'button', disabled: !redoRef.current.length, onClick: redo, title: 'بازانجام' }, '↷'),
            h('button', { type: 'button', onClick: openPreview }, 'نمایش تمام‌صفحه'),
            h('button', { type: 'button', disabled: !dirty || saving, onClick: function () { saveDraft(false); } }, saving ? 'ذخیره…' : 'ذخیره پیش‌نویس'),
            h('button', { type: 'button', className: 'button-primary', onClick: publish }, 'انتشار')
          )
        ),
        h(Notice, { value: notice, close: function () { setNotice(null); } }),
        h('div', { className: 'woact-builder-status' }, h('span', { className: 'is-live' }, '● پیش‌نمایش زنده React'), h('span', { className: dirty ? 'is-dirty' : 'is-saved' }, dirty ? 'تغییرات ذخیره‌نشده' : 'ذخیره شده'), h('span', null, working.active && working.published ? 'منتشرشده' : 'پیش‌نویس')),
        h('div', { className: 'woact-builder-body' },
          h(Palette, { manifest: payload.manifest, template: working, selected: selected, select: setSelected, quickAdd: quickAdd }),
          h(Canvas, { template: working, manifest: payload.manifest, runtimeData: payload.runtimeData, device: device, selected: selected, select: setSelected, drop: drop, addRow: addRow, removeRow: removeRow, moveRow: moveRow, removeElement: removeElement, duplicateElement: duplicateElement }),
          h(Inspector, { template: working, manifest: payload.manifest, postTypes: payload.postTypes, selected: selected, changeElement: changeElement, changeColumn: changeColumn, changeRow: changeRow, changeRowVisibility: changeRowVisibility, changeTemplate: changeTemplate, changeDocumentSetting: changeDocumentSetting, changeCondition: changeCondition, addCondition: addCondition, removeCondition: removeCondition })
        ),
        h('footer', { className: 'woact-builder-footerbar' },
          h('button', { type: 'button', onClick: duplicate }, 'تکثیر قالب'),
          h('a', { className: 'woact-builder-download', href: config.listUrl }, 'بازگشت به فهرست'),
          h('button', { type: 'button', onClick: exportTemplate }, 'خروجی JSON'),
          h('button', { type: 'button', onClick: exportAiJson }, 'JSON کامل برای هوش مصنوعی'),
          h('a', { className: 'woact-builder-download', href: payload.sampleUrl, download: 'woact-ai-header-footer-example.json' }, 'نمونه JSON هوش مصنوعی'),
          h('button', { type: 'button', onClick: copyAiPrompt }, 'کپی پرامپت AI'),
          h('button', { type: 'button', onClick: function () { importRef.current.click(); } }, 'درون‌ریزی'),
          h('input', { type: 'file', hidden: true, ref: importRef, accept: '.json,application/json', onChange: importTemplate }),
          h('button', { type: 'button', onClick: function () { setHistory(true); } }, 'تاریخچه'),
          h('button', { type: 'button', className: 'is-danger', onClick: removeTemplate }, 'حذف قالب')
        )
      ),
      h(PreviewDialog, { open: !!preview, url: preview, close: function () { setPreview(''); } }),
      h(HistoryDialog, { open: history, revisions: working.revisions || [], close: function () { setHistory(false); }, restore: restore })
    );
  }

  var root = document.getElementById('woact-layout-builder-root');
  if (!root) return;
  if (typeof e.createRoot === 'function') e.createRoot(root).render(h(App));
  else e.render(h(App), root);
})(window.wp, window.WoactBuilderConfig || {});
