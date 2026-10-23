(function (wp, config) {
  'use strict';

  if (!wp || !wp.element || !wp.apiFetch) return;

  var el = wp.element;
  var h = el.createElement;
  var Fragment = el.Fragment;
  var useEffect = el.useEffect;
  var useMemo = el.useMemo;
  var useRef = el.useRef;
  var useState = el.useState;
  var apiFetch = wp.apiFetch;

  if (config && config.nonce && apiFetch.createNonceMiddleware) {
    apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
  }

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function same(a, b) {
    return JSON.stringify(a) === JSON.stringify(b);
  }

  function conditionMatches(condition, values) {
    if (!condition) return true;
    if (Array.isArray(condition.and)) return condition.and.every(function (item) { return conditionMatches(item, values); });
    if (Array.isArray(condition.or)) return condition.or.some(function (item) { return conditionMatches(item, values); });
    var current = values[condition.field];
    var expected = condition.value;
    switch (condition.operator) {
      case 'not_equals':
        return current !== expected;
      case 'contains':
        if (Array.isArray(expected)) return expected.indexOf(current) !== -1;
        if (Array.isArray(current)) return current.indexOf(expected) !== -1;
        return String(current || '').indexOf(String(expected || '')) !== -1;
      case 'not_contains':
        return !conditionMatches({ field: condition.field, operator: 'contains', value: expected }, values);
      case 'equals':
      default:
        return current === expected;
    }
  }

  function Notice(props) {
    if (!props.notice) return null;
    return h(
      'div',
      { className: 'woact-notice woact-notice--' + (props.notice.type || 'info'), role: 'status' },
      h('span', null, props.notice.message),
      h('button', { type: 'button', onClick: props.onClose, 'aria-label': 'بستن' }, '×')
    );
  }

  function Switch(props) {
    return h(
      'button',
      {
        type: 'button',
        role: 'switch',
        'aria-checked': !!props.value,
        className: 'woact-switch ' + (props.value ? 'is-on' : ''),
        onClick: function () { props.onChange(!props.value); },
      },
      h('span', { className: 'woact-switch__knob' }),
      h('span', { className: 'woact-switch__label' }, props.value ? 'فعال' : 'غیرفعال')
    );
  }

  function MediaControl(props) {
    function choose() {
      if (!window.wp || !window.wp.media) return;
      var frame = window.wp.media({
        title: 'انتخاب رسانه',
        button: { text: 'استفاده از این فایل' },
        multiple: false,
      });
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        props.onChange(attachment.url || '');
      });
      frame.open();
    }
    return h(
      'div',
      { className: 'woact-media-control' },
      props.value ? h('img', { src: props.value, alt: '' }) : null,
      h('div', { className: 'woact-media-control__actions' },
        h('input', {
          type: 'url',
          value: props.value || '',
          placeholder: 'https://…',
          onChange: function (event) { props.onChange(event.target.value); },
        }),
        h('button', { type: 'button', className: 'button', onClick: choose }, 'انتخاب'),
        props.value ? h('button', { type: 'button', className: 'button-link-delete', onClick: function () { props.onChange(''); } }, 'حذف') : null
      )
    );
  }

  function TypographyControl(props) {
    var value = props.value || {};
    function set(key, next) {
      props.onChange(Object.assign({}, value, (function () { var out = {}; out[key] = next; return out; })()));
    }
    return h(
      'div',
      { className: 'woact-typography' },
      h('label', null, h('span', null, 'نام فونت'), h('input', {
        type: 'text',
        value: value.family || '',
        onChange: function (event) { set('family', event.target.value); },
      })),
      h('label', null, h('span', null, 'اندازه'), h('input', {
        type: 'number', min: 8, max: 120, value: value.size || 16,
        onChange: function (event) { set('size', Number(event.target.value)); },
      })),
      h('label', null, h('span', null, 'وزن'), h('select', {
        value: value.weight || '400',
        onChange: function (event) { set('weight', event.target.value); },
      }, ['100', '200', '300', '400', '500', '600', '700', '800', '900'].map(function (weight) {
        return h('option', { key: weight, value: weight }, weight);
      }))),
      h('label', null, h('span', null, 'فاصله خطوط'), h('input', {
        type: 'number', min: 1, max: 3, step: .1, value: value.lineHeight || 1.8,
        onChange: function (event) { set('lineHeight', Number(event.target.value)); },
      }))
    );
  }

  function MultiSelect(props) {
    var value = Array.isArray(props.value) ? props.value : [];
    return h('div', { className: 'woact-check-grid' }, (props.options || []).map(function (option) {
      var checked = value.indexOf(option.value) !== -1;
      return h('label', { key: option.value, className: checked ? 'is-checked' : '' },
        h('input', {
          type: 'checkbox',
          checked: checked,
          onChange: function () {
            props.onChange(checked
              ? value.filter(function (item) { return item !== option.value; })
              : value.concat([option.value]));
          },
        }),
        h('span', null, option.label)
      );
    }));
  }

  function SortableControl(props) {
    var selected = Array.isArray(props.value) ? props.value : [];
    var options = props.options || [];
    function move(index, direction) {
      var next = selected.slice();
      var target = index + direction;
      if (target < 0 || target >= next.length) return;
      var item = next[index];
      next[index] = next[target];
      next[target] = item;
      props.onChange(next);
    }
    return h(
      'div',
      { className: 'woact-sortable' },
      h(MultiSelect, { value: selected, options: options, onChange: props.onChange }),
      selected.length ? h('ol', null, selected.map(function (id, index) {
        var option = options.find(function (item) { return item.value === id; });
        return h('li', { key: id },
          h('span', { className: 'dashicons dashicons-menu' }),
          h('strong', null, option ? option.label : id),
          h('span', { className: 'woact-sortable__actions' },
            h('button', { type: 'button', disabled: index === 0, onClick: function () { move(index, -1); } }, '↑'),
            h('button', { type: 'button', disabled: index === selected.length - 1, onClick: function () { move(index, 1); } }, '↓')
          )
        );
      })) : null
    );
  }

  function RepeaterControl(props) {
    var rows = Array.isArray(props.value) ? props.value : [];
    var columns = props.field.columns || [];
    function setCell(index, column, value) {
      var next = clone(rows);
      next[index] = Object.assign({}, next[index] || {});
      next[index][column.id] = value;
      props.onChange(next);
    }
    function add() {
      var row = {};
      columns.forEach(function (column) { row[column.id] = ''; });
      props.onChange(rows.concat([row]));
    }
    function remove(index) {
      props.onChange(rows.filter(function (_, rowIndex) { return rowIndex !== index; }));
    }
    return h(
      'div',
      { className: 'woact-repeater' },
      rows.map(function (row, index) {
        return h('div', { className: 'woact-repeater__row', key: index },
          columns.map(function (column) {
            return h('label', { key: column.id },
              h('span', null, column.label),
              h('input', {
                type: column.type === 'url' ? 'url' : 'text',
                value: row[column.id] || '',
                onChange: function (event) { setCell(index, column, event.target.value); },
              })
            );
          }),
          h('button', { type: 'button', className: 'button-link-delete', onClick: function () { remove(index); } }, 'حذف')
        );
      }),
      h('button', { type: 'button', className: 'button', onClick: add }, '+ افزودن ردیف')
    );
  }

  function FieldControl(props) {
    var field = props.field;
    var value = props.value;
    var change = props.onChange;
    var options = field.type === 'post_types' ? props.postTypes : (field.options || []);

    if (field.type === 'switch') return h(Switch, { value: value, onChange: change });
    if (field.type === 'media') return h(MediaControl, { value: value, onChange: change });
    if (field.type === 'typography') return h(TypographyControl, { value: value, onChange: change });
    if (field.type === 'repeater') return h(RepeaterControl, { value: value, field: field, onChange: change });
    if (field.type === 'sortable') return h(SortableControl, { value: value, options: options, onChange: change });
    if (['multiselect', 'post_types'].indexOf(field.type) !== -1) {
      return h(MultiSelect, { value: value, options: options, onChange: change });
    }
    if (field.type === 'button_group') {
      return h('div', { className: 'woact-button-group' }, options.map(function (option) {
        return h('button', {
          type: 'button',
          key: option.value,
          className: value === option.value ? 'is-active' : '',
          onClick: function () { change(option.value); },
        }, option.label);
      }));
    }
    if (field.type === 'select') {
      return h('select', { value: value, onChange: function (event) { change(event.target.value); } },
        options.map(function (option) { return h('option', { key: option.value, value: option.value }, option.label); })
      );
    }
    if (field.type === 'range') {
      return h('div', { className: 'woact-range' },
        h('input', {
          type: 'range', min: field.min, max: field.max, step: field.step || 1, value: value,
          onChange: function (event) { change(Number(event.target.value)); },
        }),
        h('input', {
          type: 'number', min: field.min, max: field.max, step: field.step || 1, value: value,
          onChange: function (event) { change(Number(event.target.value)); },
        }),
        field.suffix ? h('span', null, field.suffix) : null
      );
    }
    if (field.type === 'number') {
      return h('div', { className: 'woact-number' },
        h('input', {
          type: 'number', min: field.min, max: field.max, step: field.step || 1, value: value,
          onChange: function (event) { change(Number(event.target.value)); },
        }),
        field.suffix ? h('span', null, field.suffix) : null
      );
    }
    if (field.type === 'color') {
      return h('div', { className: 'woact-color' },
        h('input', { type: 'color', value: value || '#000000', onChange: function (event) { change(event.target.value); } }),
        h('input', { type: 'text', dir: 'ltr', value: value || '', onChange: function (event) { change(event.target.value); } })
      );
    }
    if (field.type === 'gradient') {
      return h('div', { className: 'woact-gradient' },
        h('div', { className: 'woact-gradient__preview', style: { background: value || '' } }),
        h('input', { type: 'text', dir: 'ltr', value: value || '', onChange: function (event) { change(event.target.value); } })
      );
    }
    if (field.type === 'textarea' || field.type === 'code') {
      return h('textarea', {
        rows: field.type === 'code' ? 10 : 4,
        dir: field.type === 'code' ? 'ltr' : 'rtl',
        className: field.type === 'code' ? 'woact-code' : '',
        value: value || '',
        onChange: function (event) { change(event.target.value); },
      });
    }
    return h('input', {
      type: field.type === 'secret' ? 'password' : field.type === 'url' ? 'url' : 'text',
      dir: ['secret', 'url'].indexOf(field.type) !== -1 ? 'ltr' : 'auto',
      value: value || '',
      autoComplete: field.type === 'secret' ? 'new-password' : undefined,
      onChange: function (event) { change(event.target.value); },
    });
  }

  function Field(props) {
    var field = props.field;
    if (!conditionMatches(field.depends, props.values)) return null;
    return h(
      'div',
      { className: 'woact-field', 'data-field-id': field.id },
      h('div', { className: 'woact-field__meta' },
        h('label', { htmlFor: 'woact-' + field.id }, field.label),
        field.help ? h('p', null, field.help) : null,
        h('code', null, field.id)
      ),
      h('div', { className: 'woact-field__control', id: 'woact-' + field.id },
        h(FieldControl, {
          field: field,
          value: props.values[field.id],
          postTypes: props.postTypes,
          onChange: function (value) { props.onChange(field.id, value); },
        })
      )
    );
  }

  function Section(props) {
    var fields = props.section.fields.filter(function (field) {
      if (!props.query) return true;
      var haystack = [field.label, field.id, field.help || '', props.section.label].join(' ').toLowerCase();
      return haystack.indexOf(props.query) !== -1;
    });
    if (!fields.length) return null;
    return h(
      'section',
      { className: 'woact-section' },
      h('header', { className: 'woact-section__header' },
        h('h2', null, props.section.label),
        props.section.description ? h('p', null, props.section.description) : null
      ),
      h('div', { className: 'woact-section__body' }, fields.map(function (field) {
        return h(Field, {
          key: field.id,
          field: field,
          values: props.values,
          postTypes: props.postTypes,
          onChange: props.onChange,
        });
      }))
    );
  }

  function HistoryDialog(props) {
    if (!props.open) return null;
    return h(
      'div',
      { className: 'woact-dialog-backdrop', role: 'presentation', onMouseDown: function (event) { if (event.target === event.currentTarget) props.onClose(); } },
      h('div', { className: 'woact-dialog', role: 'dialog', 'aria-modal': 'true', 'aria-label': 'تاریخچه تنظیمات' },
        h('header', null, h('h2', null, 'تاریخچه تنظیمات'), h('button', { type: 'button', onClick: props.onClose }, '×')),
        h('div', { className: 'woact-dialog__body' },
          props.revisions.length
            ? h('ul', { className: 'woact-history' }, props.revisions.map(function (revision) {
                var date = new Date(revision.createdAt * 1000);
                return h('li', { key: revision.id },
                  h('div', null, h('strong', null, date.toLocaleString('fa-IR')), h('span', null, revision.user)),
                  h('button', { type: 'button', className: 'button', onClick: function () { props.onRestore(revision.id); } }, 'بازگردانی')
                );
              }))
            : h('p', null, 'هنوز نسخه‌ای ثبت نشده است.')
        )
      )
    );
  }

  function App() {
    var _loading = useState(true), loading = _loading[0], setLoading = _loading[1];
    var _payload = useState(null), payload = _payload[0], setPayload = _payload[1];
    var _values = useState({}), values = _values[0], setValues = _values[1];
    var _saved = useState({}), saved = _saved[0], setSaved = _saved[1];
    var _revision = useState(''), revision = _revision[0], setRevision = _revision[1];
    var _active = useState('general'), active = _active[0], setActive = _active[1];
    var _query = useState(''), query = _query[0], setQuery = _query[1];
    var _notice = useState(null), notice = _notice[0], setNotice = _notice[1];
    var _saving = useState(false), saving = _saving[0], setSaving = _saving[1];
    var _autosave = useState('idle'), autosave = _autosave[0], setAutosave = _autosave[1];
    var _history = useState(false), history = _history[0], setHistory = _history[1];
    var _draft = useState(null), draft = _draft[0], setDraft = _draft[1];
    var importRef = useRef(null);
    var dirty = !same(values, saved);

    useEffect(function () {
      apiFetch({ path: '/woact/v1/admin/settings' }).then(function (data) {
        setPayload(data);
        setValues(clone(data.values || {}));
        setSaved(clone(data.values || {}));
        setRevision(data.revision || '');
        setDraft(data.draft || null);
        if (data.schema && data.schema.groups && data.schema.groups[0]) setActive(data.schema.groups[0].id);
        setLoading(false);
      }).catch(function (error) {
        setNotice({ type: 'error', message: error.message || 'بارگذاری تنظیمات ناموفق بود.' });
        setLoading(false);
      });
    }, []);

    useEffect(function () {
      if (!payload || !dirty || !values['tools.autosave_draft']) return undefined;
      setAutosave('pending');
      var delay = Math.max(1, Number(values['tools.autosave_delay'] || 2)) * 1000;
      var timer = window.setTimeout(function () {
        apiFetch({ path: '/woact/v1/admin/settings/draft', method: 'POST', data: { values: values } })
          .then(function () { setAutosave('saved'); })
          .catch(function () { setAutosave('error'); });
      }, delay);
      return function () { window.clearTimeout(timer); };
    }, [values, payload]);

    useEffect(function () {
      function warn(event) {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
      }
      window.addEventListener('beforeunload', warn);
      return function () { window.removeEventListener('beforeunload', warn); };
    }, [dirty]);

    var groups = payload && payload.schema ? payload.schema.groups || [] : [];
    var normalizedQuery = query.trim().toLowerCase();
    var visibleGroups = useMemo(function () {
      if (!normalizedQuery) return groups.filter(function (group) { return group.id === active; });
      return groups.filter(function (group) {
        return group.label.toLowerCase().indexOf(normalizedQuery) !== -1
          || group.sections.some(function (section) {
            return section.label.toLowerCase().indexOf(normalizedQuery) !== -1
              || section.fields.some(function (field) {
                return [field.label, field.id, field.help || ''].join(' ').toLowerCase().indexOf(normalizedQuery) !== -1;
              });
          });
      });
    }, [groups, active, normalizedQuery]);

    function update(id, value) {
      setValues(function (current) {
        var next = Object.assign({}, current);
        next[id] = value;
        return next;
      });
    }

    function save() {
      if (!dirty || saving) return;
      setSaving(true);
      apiFetch({ path: '/woact/v1/admin/settings', method: 'POST', data: { values: values, revision: revision } })
        .then(function (data) {
          setValues(clone(data.values));
          setSaved(clone(data.values));
          setRevision(data.revision);
          setPayload(function (current) { return Object.assign({}, current, { revisions: data.revisions || [] }); });
          setDraft(null);
          setNotice({ type: 'success', message: 'تنظیمات با موفقیت ذخیره شد.' });
        })
        .catch(function (error) {
          setNotice({ type: 'error', message: error.message || 'ذخیره تنظیمات ناموفق بود.' });
        })
        .finally(function () { setSaving(false); });
    }

    function applyPreset(event) {
      var id = event.target.value;
      if (!id) return;
      var preset = (payload.presets || []).find(function (item) { return item.id === id; });
      if (preset) {
        setValues(Object.assign({}, values, preset.values));
        setNotice({ type: 'info', message: 'پیش‌تنظیم «' + preset.label + '» اعمال شد؛ برای نهایی‌شدن ذخیره کنید.' });
      }
      event.target.value = '';
    }

    function exportSettings() {
      var documentData = {
        product: 'woact',
        version: config.version,
        schemaVersion: payload.schema.version,
        exportedAt: new Date().toISOString(),
        values: values,
      };
      var blob = new Blob([JSON.stringify(documentData, null, 2)], { type: 'application/json;charset=utf-8' });
      var url = URL.createObjectURL(blob);
      var link = document.createElement('a');
      link.href = url;
      link.download = 'woact-settings-' + new Date().toISOString().slice(0, 10) + '.json';
      link.click();
      URL.revokeObjectURL(url);
    }

    function importSettings(event) {
      var file = event.target.files && event.target.files[0];
      event.target.value = '';
      if (!file) return;
      file.text().then(function (text) {
        var documentData = JSON.parse(text);
        return apiFetch({
          path: '/woact/v1/admin/settings/import',
          method: 'POST',
          data: { document: documentData, revision: revision },
        });
      }).then(function (data) {
        setValues(clone(data.values));
        setSaved(clone(data.values));
        setRevision(data.revision);
        setPayload(function (current) { return Object.assign({}, current, { revisions: data.revisions || [] }); });
        setNotice({ type: 'success', message: 'تنظیمات با موفقیت درون‌ریزی شد.' });
      }).catch(function (error) {
        setNotice({ type: 'error', message: error.message || 'فایل تنظیمات معتبر نیست.' });
      });
    }

    function reset(groupId) {
      var message = groupId ? 'تنظیمات همین بخش به مقدار پیش‌فرض بازگردد؟' : 'تمام تنظیمات Woact به مقدار پیش‌فرض بازگردد؟';
      if (!window.confirm(message)) return;
      apiFetch({ path: '/woact/v1/admin/settings/reset', method: 'POST', data: { group: groupId || '' } })
        .then(function (data) {
          setValues(clone(data.values));
          setSaved(clone(data.values));
          setRevision(data.revision);
          setPayload(function (current) { return Object.assign({}, current, { revisions: data.revisions || [] }); });
          setNotice({ type: 'success', message: 'تنظیمات بازنشانی شد.' });
        })
        .catch(function (error) { setNotice({ type: 'error', message: error.message || 'بازنشانی ناموفق بود.' }); });
    }

    function restore(revisionId) {
      if (!window.confirm('این نسخه بازگردانی شود؟ وضعیت فعلی در تاریخچه حفظ خواهد شد.')) return;
      apiFetch({ path: '/woact/v1/admin/settings/restore', method: 'POST', data: { revisionId: revisionId } })
        .then(function (data) {
          setValues(clone(data.values));
          setSaved(clone(data.values));
          setRevision(data.revision);
          setPayload(function (current) { return Object.assign({}, current, { revisions: data.revisions || [] }); });
          setHistory(false);
          setNotice({ type: 'success', message: 'نسخهٔ انتخاب‌شده بازگردانی شد.' });
        })
        .catch(function (error) { setNotice({ type: 'error', message: error.message || 'بازگردانی ناموفق بود.' }); });
    }

    if (loading) {
      return h('div', { className: 'woact-admin-loading' }, h('span', { className: 'spinner is-active' }), h('p', null, 'در حال بارگذاری تنظیمات…'));
    }
    if (!payload) {
      return h('div', { className: 'woact-fatal' }, h('h1', null, 'Woact'), h('p', null, notice ? notice.message : 'اطلاعات تنظیمات دریافت نشد.'));
    }

    return h(
      Fragment,
      null,
      h('div', { className: 'woact-admin-shell' },
        h('aside', { className: 'woact-sidebar' },
          h('div', { className: 'woact-brandmark' },
            h('div', { className: 'woact-brandmark__logo' }, 'W'),
            h('div', null, h('strong', null, 'Woact'), h('span', null, 'تنظیمات نسخه ' + payload.context.version))
          ),
          h('div', { className: 'woact-search' },
            h('span', { className: 'dashicons dashicons-search' }),
            h('input', {
              type: 'search', value: query, placeholder: 'جست‌وجو در تنظیمات…',
              onChange: function (event) { setQuery(event.target.value); },
            })
          ),
          h('nav', { className: 'woact-groups', 'aria-label': 'گروه‌های تنظیمات' }, groups.map(function (group) {
            return h('button', {
              type: 'button', key: group.id,
              className: !normalizedQuery && active === group.id ? 'is-active' : '',
              onClick: function () { setActive(group.id); setQuery(''); },
            },
            h('span', { className: 'dashicons ' + (group.icon || 'dashicons-admin-generic') }),
            h('span', null, group.label)
            );
          })),
          h('div', { className: 'woact-sidebar__footer' }, 'Schema v' + payload.schema.version)
        ),
        h('div', { className: 'woact-workspace' },
          h('header', { className: 'woact-toolbar' },
            h('div', { className: 'woact-toolbar__title' },
              h('h1', null, normalizedQuery ? 'نتایج جست‌وجو' : ((groups.find(function (group) { return group.id === active; }) || {}).label || 'تنظیمات')),
              h('div', { className: 'woact-save-state ' + (dirty ? 'is-dirty' : '') },
                dirty ? 'تغییرات ذخیره‌نشده' : 'همه تغییرات ذخیره شده',
                autosave === 'pending' ? ' · ذخیره پیش‌نویس…' : autosave === 'saved' && dirty ? ' · پیش‌نویس ذخیره شد' : ''
              )
            ),
            h('div', { className: 'woact-toolbar__actions' },
              h('select', { defaultValue: '', onChange: applyPreset, 'aria-label': 'پیش‌تنظیم‌ها' },
                h('option', { value: '' }, 'اعمال پیش‌تنظیم…'),
                (payload.presets || []).map(function (preset) { return h('option', { key: preset.id, value: preset.id }, preset.label); })
              ),
              h('button', { type: 'button', className: 'button', onClick: exportSettings }, 'خروجی'),
              h('button', { type: 'button', className: 'button', onClick: function () { importRef.current.click(); } }, 'درون‌ریزی'),
              h('input', { ref: importRef, type: 'file', accept: 'application/json,.json', hidden: true, onChange: importSettings }),
              h('button', { type: 'button', className: 'button', onClick: function () { setHistory(true); } }, 'تاریخچه'),
              h('button', { type: 'button', className: 'button button-primary woact-save', disabled: !dirty || saving, onClick: save }, saving ? 'در حال ذخیره…' : 'ذخیره تنظیمات')
            )
          ),
          h(Notice, { notice: notice, onClose: function () { setNotice(null); } }),
          draft && draft.updatedAt ? h('div', { className: 'woact-draft-banner' },
            h('div', null, h('strong', null, 'پیش‌نویس ذخیره‌شده موجود است.'), h('span', null, new Date(draft.updatedAt * 1000).toLocaleString('fa-IR'))),
            h('div', null,
              h('button', { type: 'button', className: 'button button-primary', onClick: function () { setValues(clone(draft.values)); setDraft(null); } }, 'بازیابی پیش‌نویس'),
              h('button', { type: 'button', className: 'button', onClick: function () { setDraft(null); } }, 'نادیده گرفتن')
            )
          ) : null,
          h('main', { className: 'woact-settings-content' },
            visibleGroups.length ? visibleGroups.map(function (group) {
              return h('div', { className: 'woact-group', key: group.id },
                normalizedQuery ? h('h2', { className: 'woact-search-group-title' }, group.label) : null,
                group.sections.map(function (section) {
                  return h(Section, {
                    key: section.id,
                    section: section,
                    query: normalizedQuery,
                    values: values,
                    postTypes: payload.context.postTypes || [],
                    onChange: update,
                  });
                }),
                !normalizedQuery ? h('div', { className: 'woact-reset-row' },
                  h('button', { type: 'button', className: 'button-link-delete', onClick: function () { reset(group.id); } }, 'بازنشانی این بخش')
                ) : null
              );
            }) : h('div', { className: 'woact-empty-search' }, h('span', { className: 'dashicons dashicons-search' }), h('h2', null, 'نتیجه‌ای پیدا نشد'))
          ),
          h('footer', { className: 'woact-bottom-bar' },
            h('button', { type: 'button', className: 'button-link-delete', onClick: function () { reset(null); } }, 'بازنشانی همه تنظیمات'),
            h('button', { type: 'button', className: 'button button-primary', disabled: !dirty || saving, onClick: save }, saving ? 'در حال ذخیره…' : 'ذخیره تنظیمات')
          )
        )
      ),
      h(HistoryDialog, {
        open: history,
        revisions: payload.revisions || [],
        onClose: function () { setHistory(false); },
        onRestore: restore,
      })
    );
  }

  var root = document.getElementById('woact-settings-root');
  if (!root) return;
  if (typeof el.createRoot === 'function') {
    el.createRoot(root).render(h(App));
  } else {
    el.render(h(App), root);
  }
})(window.wp, window.WoactAdminConfig || {});

