(function (runtime) {
  'use strict';
  if (!runtime) return;
  runtime.register('notice_card', function (context) {
    var h = context.h, p = context.props, common = context.common;
    return h('aside', Object.assign({}, common, { className: common.className + ' woact-notice-card is-' + (p.tone || 'brand') }),
      h('div', { className: 'woact-notice-card__body' },
        p.eyebrow ? h('span', { className: 'woact-notice-card__eyebrow' }, p.eyebrow) : null,
        h('h3', null, p.title || ''),
        h('p', null, p.text || '')
      ),
      p.buttonLabel ? h('a', { className: 'woact-notice-card__button', href: p.buttonUrl || '#' }, p.buttonLabel) : null
    );
  }, { version: '1.1.0' });
})(window.WoactWidgetRuntime);
