# Woact Widget Packages

هر ویجت مستقل در یک پوشه قرار می‌گیرد و حداقل فایل `widget.php` دارد. فایل‌های اختیاری `runtime.js` و `style.css` فقط هنگامی بارگذاری می‌شوند که ویجت در سند استفاده شده باشد؛ در ویرایشگر برای پیش‌نمایش زنده بارگذاری می‌شوند.

```text
widgets/my-widget/
├── widget.php   # تعریف، کنترل‌ها، نسخه و migration
├── runtime.js   # ثبت Renderer در WoactWidgetRuntime
└── style.css    # استایل محدود به همان ویجت
```

برای ساخت بستهٔ جدید اجرا کنید: `npm run create-widget -- my-widget "ویجت من"`.
