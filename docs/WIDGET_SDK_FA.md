# راهنمای Widget SDK ووَکت

در نسخهٔ `1.0.6-rc1` ویجت‌ها از رجیستری واحد و بسته‌های مستقل خوانده می‌شوند. سریع‌ترین راه ساخت ویجت داخلی:

```bash
npm run create-widget -- my-widget "ویجت من"
```

این فرمان پوشهٔ `widgets/my-widget` را با `widget.php`، `runtime.js` و `style.css` می‌سازد. هسته پوشه را خودکار کشف می‌کند، Renderer را در ویرایشگر زنده بارگذاری می‌کند و در سایت JS/CSS را فقط وقتی همان ویجت استفاده شده باشد enqueue می‌کند.

افزونهٔ ثالث نیز می‌تواند بدون ویرایش هسته، تعریف خود را روی اکشن `woact_register_widgets` ثبت کند:

```php
add_action('woact_register_widgets', function (Woact_Widget_Registry $registry): void {
    $registry->register([
        'type' => 'vendor_card',
        'label' => 'کارت اختصاصی',
        'icon' => 'id',
        'category' => 'business_widgets',
        'version' => '1.0.0',
        'defaults' => ['title' => 'عنوان کارت'],
        'fields' => [
            ['id' => 'title', 'label' => 'عنوان', 'type' => 'text'],
        ],
        'dataGroup' => '',
        'assets' => ['vendor-card'],
        'dependencies' => [],
        'migrations' => [],
    ]);
});
```

تابع `woact_register_widget($definition)` نیز برای ثبت مستقیم در دسترس است. مقدار `type` باید یکتا و شامل حروف کوچک، عدد و underscore باشد. سند صفحه فقط ویجت‌های ثبت‌شده را می‌پذیرد و ورودی‌های متنی، URL و HTML را هنگام ذخیره پاک‌سازی می‌کند.

برای دادهٔ پویا می‌توان `register_resolver($group, $callback)` یا فیلتر `woact_widget_runtime_data` را استفاده کرد. داده فقط برای گروه‌هایی resolve می‌شود که واقعاً در سند صفحه مصرف شده‌اند. برای تغییر امن props قدیمی، callbackهای `migrations` به ترتیب نسخه اجرا می‌شوند.

Renderer جاوااسکریپت با API عمومی زیر ثبت می‌شود:

```js
window.WoactWidgetRuntime.register('vendor_card', function (context) {
  return context.h('article', context.common, context.props.title);
}, { version: '1.0.0' });
```

`context` شامل `h`، `node`، `props`، `common`، `style`، `data`، `widgets`، `children` و `device` است. ویجت نمونهٔ کامل `notice-card` در پوشهٔ `widgets` قرار دارد.
