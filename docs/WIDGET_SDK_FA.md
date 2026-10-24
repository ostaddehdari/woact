# راهنمای Widget SDK ووَکت

در نسخهٔ `0.5.1-rc1` همهٔ ویجت‌های صفحه‌ساز از رجیستری واحد خوانده می‌شوند. افزونهٔ ثالث می‌تواند بدون ویرایش هسته، ویجت خود را روی اکشن `woact_register_widgets` ثبت کند:

```php
add_action('woact_register_widgets', function (Woact_Widget_Registry $registry): void {
    $registry->register([
        'type' => 'vendor_card',
        'label' => 'کارت اختصاصی',
        'icon' => 'id',
        'category' => 'business_widgets',
        'defaults' => ['title' => 'عنوان کارت'],
        'fields' => [
            ['id' => 'title', 'label' => 'عنوان', 'type' => 'text'],
        ],
        'dataGroup' => '',
        'assets' => [],
    ]);
});
```

تابع `woact_register_widget($definition)` نیز برای ثبت مستقیم در دسترس است. مقدار `type` باید یکتا و شامل حروف کوچک، عدد و underscore باشد. سند صفحه فقط ویجت‌های ثبت‌شده را می‌پذیرد و ورودی‌های متنی، URL و HTML را هنگام ذخیره پاک‌سازی می‌کند.

برای دادهٔ پویا می‌توان فیلتر `woact_widget_runtime_data` را استفاده کرد. داده فقط برای گروه‌هایی resolve می‌شود که واقعاً در سند صفحه مصرف شده‌اند. فهرست assetهای لازم نیز از `required_assets()` به دست می‌آید.

برای افزودن renderer اختصاصی به runtime فعلی، افزونه باید نوع ثبت‌شده را به بستهٔ JavaScript خود نگاشت کند. API عمومی renderer سفارشی در نسخهٔ بعدی تثبیت خواهد شد؛ بنابراین در این RC بهتر است از ویجت‌های داخلی یا fork کنترل‌شده استفاده شود.
