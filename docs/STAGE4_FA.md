# استیج ۴ Woact — صفحه‌ساز React

نسخهٔ این استیج `0.4.0-rc1` است. صفحه‌ساز از منوی `Woact ← صفحه‌ساز React` در دسترس است و مستقیماً برگه‌های واقعی وردپرس را مدیریت می‌کند.

## قابلیت‌های نسخهٔ نخست

- ساخت و انتخاب برگه بدون خروج از سازنده
- Canvas زنده با همان کامپوننت‌های React خروجی سایت
- درخت تو‌در‌توی Section، Container، Columns و Column
- کشیدن و رهاکردن، Navigator، Duplicate، Lock، حذف و Copy/Paste
- Inspector سه‌قسمتی محتوا، استایل و پیشرفته
- تنظیم مستقل Desktop، Tablet و Mobile با ارث‌بری
- اتصال اولیه به داده‌های پویا: عنوان، خلاصه، تصویر شاخص، نام سایت و نام کاربر
- Undo/Redo و میانبرهای `Ctrl+C`، `Ctrl+V` و `Ctrl+Z`
- ذخیره خودکار، کنترل تعارض و جداسازی Draft از Published
- تاریخچهٔ انتشار در لایهٔ API و نگهداری ۳۰ نسخه
- Import و Export سند JSON نسخه‌دار
- رندر Frontend و fallback سمت PHP برای موتور جست‌وجو و JavaScript غیرفعال
- حفظ صفحات Elementor و سایر صفحه‌سازها تا زمان فعال‌سازی صریح React

## قرارداد سند

سند صفحه مستقل از UI ادیتور است. هر Node شامل `id`، `type`، `props`، `style`، `responsive`، `visibility`، `dynamic`، `locked` و `children` است. بنابراین در نسخه‌های بعدی می‌توان Adapter ادیتور را تغییر داد، بدون تغییر داده‌های ذخیره‌شده.

## انتشار

«ذخیره» فقط Draft را تغییر می‌دهد. «انتشار» Snapshot فعلی را به Published منتقل، رندر صفحه را React و وضعیت برگه را منتشر می‌کند. صفحه‌های Elementor به‌صورت خودکار تبدیل نمی‌شوند.

## بسته‌ها

- `woact-theme-0.4.0-rc1.zip`
- `woact-core-0.4.0-rc1.zip`
- `woact-stage4-0.4.0-rc1.zip`
