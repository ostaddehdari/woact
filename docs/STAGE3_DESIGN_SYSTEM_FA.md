# استیج ۳ جدید Woact — سیستم طراحی مشترک

نسخه `0.8.0-rc1` تنظیمات ظاهری و پیشرفتهٔ همهٔ المان‌های صفحه‌ساز را از کد هر ویجت جدا می‌کند. هر ویجت فقط فیلدهای محتوایی خودش را تعریف می‌کند و تب‌های «استایل» و «پیشرفته» از `WoactDesignSystem` دریافت می‌شوند.

## ده ورک انجام‌شده

1. پنل یکپارچهٔ محتوا، استایل و پیشرفته
2. طرح‌بندی: margin، padding، width، min-height، position و z-index
3. موشن افکت: ورود، مدت، تأخیر و hover
4. Transform: جابه‌جایی، scale، rotate و skew
5. پس‌زمینهٔ کلاسیک، تصویر و گرادیان
6. حاشیه، radius و box shadow
7. ماسک‌های circle، ellipse، triangle، diamond، hexagon و blob
8. visibility مستقل دسکتاپ، تبلت و موبایل
9. CSS ID، CSS class و Attributeهای امن HTML
10. CSS سفارشی scope‌شده با کلیدواژه `selector`

تنظیمات در فیلد نسخه‌پذیر `design` هر node ذخیره می‌شوند. Renderer زنده و Renderer سایت از همین داده استفاده می‌کنند. PHP نیز داده را دوباره پاک‌سازی می‌کند و Attributeها را به `data-*`، `aria-*`، `title`، `role` و `tabindex` محدود می‌سازد.

## توسعهٔ ویجت جدید

ویجت تازه فقط به تعریف `type`، `label`، `category`، `defaults` و `fields` نیاز دارد. تنظیمات طراحی مشترک خودکار در Inspector نمایش داده می‌شوند و نیازی به تکرار کنترل‌های تایپوگرافی، پس‌زمینه، حاشیه یا Advanced نیست.
