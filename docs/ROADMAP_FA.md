# نقشه‌راه اجرایی Woact

این سند مبنای اجرای پروژهٔ **Woact** است. پروژه در پنج استیج و پنجاه ورک پیاده‌سازی می‌شود. هر ورک باید کد، مستندات لازم، معیار پذیرش و آزمون متناسب با ریسک خود را داشته باشد.

## تصمیم‌های معماری پایه

- مخزن به‌صورت monorepo سازمان‌دهی می‌شود: قالب نصب‌شوندهٔ `woact`، افزونهٔ هستهٔ `woact-core`، بستهٔ UI، موتور رندر، Schemaها، سازنده‌ها و بسته‌های ویجت.
- وردپرس منبع اصلی محتوا، رسانه، کاربر، مجوز، مسیرها، بازبینی و داده‌های ووکامرس است.
- JSON نسخه‌دار، قرارداد مشترک تنظیمات، هدر/فوتر، صفحات و ویجت‌ها خواهد بود.
- خروجی صفحات Woact با کامپوننت‌های React تولید می‌شود. برای SEO و نمایش اولیه، مسیر پیش‌رندر/SSR و Hydration در معماری پیش‌بینی می‌شود.
- سرور نهایی برای اجرای بسته‌های ساخته‌شده به Node.js نیاز ندارد؛ Node.js فقط برای توسعه و Build لازم است. رندر پویا با Node در آینده می‌تواند گزینه‌ای اختیاری باشد.
- داده و منطق ماندگار در افزونهٔ هسته قرار می‌گیرد تا تعویض قالب موجب از دست‌رفتن محتوا نشود.

---

## استیج ۱: قالب وردپرس React

### W1.1 — پایه‌گذاری monorepo و قرارداد بسته‌ها
ساخت ساختار پروژه برای قالب، افزونهٔ هسته، UI، Renderer، Schema و ابزار Build؛ تعریف TypeScript، lint، format، نسخه‌بندی و قواعد نام‌گذاری.

### W1.2 — پوستهٔ نصب‌شوندهٔ وردپرس
ایجاد فایل‌های استاندارد قالب، `style.css`، `functions.php`، قالب‌های fallback، پشتیبانی از `wp_head`، `wp_body_open`، `wp_footer`، منو، لوگو، تصویر شاخص، ترجمه و RTL.

### W1.3 — راه‌اندازی React Runtime و App Shell
ساخت Root اصلی React، Error Boundary، Loading Boundary، مدیریت Context، Layout عمومی، اتصال Header/Main/Footer و کنترل Mount/Unmount امن.

### W1.4 — مسیریابی هماهنگ با WordPress
تعریف قرارداد مسیر بین WordPress و React برای صفحه، نوشته، بایگانی، جست‌وجو، 404، محصولات و endpointهای حساب کاربری؛ حفظ canonical URL و تاریخچهٔ مرورگر.

### W1.5 — لایهٔ داده و REST API
ساخت Bootstrap Payload و endpointهای نسخه‌دار برای تنظیمات عمومی، منوها، محتوای صفحه، nonce، وضعیت کاربر و داده‌های لازم؛ کش، validation و permission برای هر مسیر.

### W1.6 — رندر اولیه، SEO و Hydration
تعریف مسیر پیش‌رندر هنگام انتشار و Hydration در مرورگر؛ تولید عنوان، meta، canonical، Open Graph و Schema؛ fallback قابل‌خزش برای مواقع غیرفعال‌بودن JavaScript.

### W1.7 — Design System پایه و RTL/LTR
تعریف design tokenها، CSS variables، رنگ، تایپوگرافی، فاصله، radius، سایه، breakpointها و کامپوننت‌های پایه با پشتیبانی کامل فارسی، RTL و LTR.

### W1.8 — مدیریت Asset و کارایی
راه‌اندازی Vite، manifest خوان PHP، code splitting، lazy loading، حذف کد مرده، preload کنترل‌شده، cache busting و بارگذاری فایل فقط در صفحات موردنیاز.

### W1.9 — سازگاری، امنیت و دسترس‌پذیری پایه
Escape و sanitize، CSP-friendly code، کنترل nonce و capability، navigation با صفحه‌کلید، focus management، semantic HTML و عدم تداخل CSS با افزونه‌ها.

### W1.10 — بستهٔ نصب، CI و Release Candidate
ساخت اسکریپت تولید ZIP تمیز، Theme Check، PHP lint، TypeScript build، آزمون نصب/فعال‌سازی و GitHub Actions برای تولید artifact نسخهٔ `0.1.0-rc1`.

**دروازهٔ خروج استیج ۱:** قالب ZIP روی وردپرس تازه نصب شود، مسیرهای اصلی را با React نمایش دهد، RTL و fallback سالم داشته باشد و Build تولیدی بدون Node.js اجرا شود.

---

## استیج ۲: صفحهٔ تنظیمات در داشبورد

### W2.1 — تبدیل تنظیمات مرجع به Settings Schema
استخراج و بازطراحی همهٔ گروه‌های فایل مرجع در Schema نسخه‌دار؛ پوشش عمومی، عنوان صفحه، پاورقی، تایپوگرافی، رنگ، وبلاگ، نمونه‌کار، فروشگاه، محصول، حساب، شبکه‌های اجتماعی، API، عملکرد و ابزارهای نگهداری.

### W2.2 — پوستهٔ React تنظیمات با UI فارسی
ساخت صفحهٔ تمام‌عرض RTL با منوی گروهی، زیرمنو، جست‌وجوی سریع، breadcrumb، نشانگر تغییرات ذخیره‌نشده، ذخیرهٔ چسبان و نمایش مناسب موبایل و دسکتاپ.

### W2.3 — موتور تولید فرم از Schema
پیاده‌سازی renderer برای switch، text، textarea، number، range، select، multi-select، button group، color، gradient، typography، media، icon، repeater، code editor و sortable list.

### W2.4 — Dependency و Conditional Logic
ساخت قواعد `equals`، `not_equals`، `contains`، AND/OR، نمایش/مخفی‌سازی، enable/disable، مقدار محاسبه‌شده و پاک‌سازی گزینه‌های وابسته بدون از بین بردن ناخواستهٔ داده.

### W2.5 — ذخیره‌سازی، اعتبارسنجی و API تنظیمات
ایجاد REST Controller نسخه‌دار، schema validation سمت سرور، sanitize جداگانه برای هر نوع، capability checks، optimistic concurrency، autosave draft و ثبت تاریخ تغییرات.

### W2.6 — تنظیمات عمومی و هویت بصری
پیاده‌سازی چیدمان، بنر سربرگ، popup، تأیید سن، کوکی، نوار موبایل، جست‌وجو، پیمایش چسبان، عنوان صفحه، فوتر، typography، فونت سفارشی، رنگ، پس‌زمینه، دکمه، فرم و اعلان.

### W2.7 — تنظیمات محتوا و ساختار صفحات
پیاده‌سازی کامل تنظیمات وبلاگ، نوشتهٔ تکی، نمونه‌کار، آرشیو نمونه‌کار، پروژهٔ تکی، sidebars، breadcrumbs، pagination، share و layoutهای عمومی.

### W2.8 — تنظیمات WooCommerce
پیاده‌سازی فروشگاه، محصولات متغیر، برچسب و برند، quick view، مقایسه، علاقه‌مندی، انتظار، سبد، پرداخت، تشکر، بسته‌های خرید، هدیه، ارسال رایگان، تحویل، سبد رهاشده، ردیاب قیمت، آرشیو و محصول تکی.

### W2.9 — Preset، Import/Export و نگهداری
ساخت presetهای نام‌دار، export/import نسخه‌دار، preview تغییرات، reset گروهی و کامل، migration تنظیمات، حالت تعمیر، white label، custom CSS/JS با کنترل دسترسی و rollback.

### W2.10 — کارایی، آزمون و ماتریس سازگاری تنظیمات
Virtualized rendering برای فرم بزرگ، جست‌وجوی سریع میان گزینه‌ها، آزمون انواع فیلد و dependency، نقش‌های کاربری، multisite، RTL/LTR، migration نسخه‌ها و بازیابی تنظیمات خراب.

**دروازهٔ خروج استیج ۲:** تمام گروه‌های مرجع در پنل Woact قابل جست‌وجو، ویرایش، اعتبارسنجی، ذخیره، export/import و rollback باشند و تغییرات منتخب در سایت اعمال شوند.

**وضعیت:** پیاده‌سازی‌شده در نسخه `0.2.0-rc1`.

---

## استیج ۳: هدر و فوتر ساز

### W3.1 — مدل سند نسخه‌دار Header/Footer
تعریف Schema برای ردیف، ستون، zone، عنصر، breakpoint، style، شرط نمایش و reference؛ جداسازی draft، published و revision.

### W3.2 — Canvas کشیدن و رهاکردن
ساخت محیط React با drag/drop، انتخاب، reorder، duplicate، delete، nesting کنترل‌شده، zoom و نمایش دقیق drop indicator.

### W3.3 — سازندهٔ ردیف، ستون و ناحیه
پشتیبانی از flex/grid، عرض محتوا، full width، ارتفاع، gap، alignment، sticky، overlay و ناحیه‌های desktop/tablet/mobile.

### W3.4 — عناصر عمومی هدر
افزودن لوگو، منو، جست‌وجو، دکمه، HTML امن، آیکون، متن، شبکه اجتماعی، زبان، breadcrumb، spacer و divider.

### W3.5 — عناصر هدر فروشگاهی و کاربر
افزودن حساب کاربری، سبد خرید، mini cart، علاقه‌مندی، مقایسه، دسته‌بندی محصولات و badgeهای شمارنده با دادهٔ واقعی ووکامرس.

### W3.6 — عناصر و layoutهای فوتر
ستون‌های محتوایی، منو، ابزارک، خبرنامه، اطلاعات تماس، شبکه اجتماعی، copyright، trust badge، پرداخت‌ها، pre-footer و footer bar موبایل.

### W3.7 — تنظیمات Responsive و رفتار پیمایش
نمایش/عدم نمایش بر اساس breakpoint، header چسبان، shrink on scroll، transparent/overlap، mobile drawer، accessible menu و مدیریت focus.

### W3.8 — Template، Preset و Display Conditions
ذخیرهٔ قالب، clone، import/export، header/footer پیش‌فرض و شروط نمایش بر اساس نوع محتوا، دسته، محصول، نقش کاربر، زبان و دستگاه.

### W3.9 — موتور Render مشترک و Preview واقعی
استفاده از همان کامپوننت‌ها در ویرایشگر و سایت، preview داخل iframe هم‌مبدأ، data resolver، error isolation، cache و تطابق draft/published.

### W3.10 — انتشار، بازبینی و آزمون سازنده
autosave، undo/redo، revision، publish، rollback، lock ویرایش، تست drag/drop، دسترس‌پذیری، موبایل، منوهای بزرگ و سازگاری ووکامرس.

**دروازهٔ خروج استیج ۳:** مدیر بتواند چند هدر و فوتر بسازد، برای آن‌ها شرط نمایش تعیین کند و خروجی React سایت با پیش‌نمایش ویرایشگر تطابق داشته باشد.

**وضعیت:** پیاده‌سازی‌شده در نسخه `0.3.0-rc1`.

---

## استیج ۴: صفحه‌ساز شبیه المنتور

### W4.1 — انتخاب و محصورسازی هستهٔ Editor
ارزیابی Puck و گزینه‌های MIT، انتخاب هسته، قراردادن آن پشت Adapter اختصاصی Woact و جلوگیری از وابستگی Schema صفحات به API داخلی کتابخانه.

### W4.2 — Page Document و Component Registry
تعریف JSON نسخه‌دار صفحه، root props، slots، nested zones، component manifest، default props، validation، migration و شناسه‌های پایدار.

### W4.3 — ویرایشگر Canvas و درخت ساختار
ساخت drag/drop تو‌در‌تو، navigator، breadcrumb عناصر، انتخاب چندگانه، copy/paste، duplicate، lock، hide و جابه‌جایی دقیق درخت.

### W4.4 — Inspector و کنترل‌های محتوا/استایل
پنل‌های محتوا، استایل و پیشرفته؛ typography، spacing، sizing، border، background، effects، position، z-index، custom attributes و کنترل‌های اختصاصی هر ویجت.

### W4.5 — Responsive Editor
breakpointهای قابل تنظیم، preview دستگاه، مقادیر ارث‌بر، overrideهای هر دستگاه، visibility، container query آماده و کنترل overflow.

### W4.6 — Global Design، Component و Symbol
رنگ و فونت سراسری، class/token، بخش ذخیره‌شده، component قابل استفادهٔ مجدد، props قابل ویرایش، sync/unsync و بروزرسانی مصرف‌کنندگان.

### W4.7 — Dynamic Data و Query Builder
اتصال property به نوشته، کاربر، taxonomy، custom field و WooCommerce؛ query builder امن، loop، شرط نمایش، fallback و preview context.

### W4.8 — تجربهٔ ویرایش حرفه‌ای
undo/redo، autosave، revision، keyboard shortcuts، command palette، history، responsive preview، role permissions، safe mode و recovery سند خراب.

### W4.9 — انتشار React، SEO و Performance
رندر دقیق از Registry، code splitting بر اساس ویجت‌های مصرف‌شده، preload داده، پیش‌رندر/SSR، Hydration، cache invalidation و تولید محتوای قابل ایندکس.

### W4.10 — SDK توسعه و مهاجرت صفحه
API ثبت ویجت، کنترل و data source توسط افزونه‌های دیگر؛ CLI/scaffold، مستندات توسعه، migrationهای Schema و import اولیه برای الگوهای منتخب Elementor/Gutenberg.

**دروازهٔ خروج استیج ۴:** کاربر بدون کدنویسی صفحه بسازد، خروجی JSON نسخه‌دار ذخیره شود و همان صفحه با کامپوننت‌های واقعی React، قابل ویرایش مجدد و قابل مهاجرت نمایش داده شود.

---

## استیج ۵: ویجت‌های عمومی و تخصصی وردپرس و ووکامرس

### W5.1 — Widget SDK و Registry قابل توسعه
تعریف قرارداد ثبت ویجت، دسته، icon، fields، render، resolver، asset، permission، version و migration؛ بارگذاری ویجت افزونه‌های ثالث بدون ویرایش هسته.

### W5.2 — ویجت‌های پایهٔ چیدمان و محتوا
Container، Grid، Columns، Stack، Heading، Text، Button، Icon، Image، Divider، Spacer، Badge، List و HTML امن.

### W5.3 — ویجت‌های رسانه و تعامل
Gallery، Video، Audio، Carousel، Slider، Tabs، Accordion، Modal، Tooltip، Countdown، Counter، Progress و Lottie با lazy loading.

### W5.4 — ویجت‌های سایت شخصی و شرکتی
Hero، Services، Team، Testimonial، Client Logos، Pricing، FAQ، Timeline، Portfolio، Contact CTA، Map و Social Profiles.

### W5.5 — ویجت‌های WordPress و محتوای پویا
Post Grid/List/Carousel، Single Post، Author، Comments، Categories، Tags، Search، Breadcrumb، Menu، Login، User Profile و shortcode bridge کنترل‌شده.

### W5.6 — فرم و Lead Generation
Form Builder سبک، فیلدها، validation، file upload، anti-spam، email/webhook action، consent، پیام موفق/خطا و اتصال‌پذیری به افزونه‌های فرم معروف.

### W5.7 — ویجت‌های آرشیو ووکامرس
Product Grid/List/Carousel، Categories، Filters، Sort، Results Count، Pagination، Sale/Featured/Recent، Quick View و Add to Cart.

### W5.8 — ویجت‌های محصول تکی ووکامرس
Title، Gallery، Price، Rating، Stock، Variation، Add to Cart، Buy Now، Meta، Tabs، Reviews، Related، Upsell، Bundle و Delivery Estimate.

### W5.9 — ویجت‌های خرید و حساب کاربری
Mini Cart، Cart، Coupon، Checkout، Order Review، Thank You، Account Navigation، Orders، Downloads، Addresses، Wishlist، Compare و Waitlist با fallback سازگار.

### W5.10 — کیفیت، کارایی و ماتریس انتشار ویجت‌ها
visual regression، unit/integration، accessibility، RTL/LTR، responsive، امنیت ورودی/خروجی، WooCommerce Store API، cart/checkout classic و blocks، profiling و مستندات هر ویجت.

**دروازهٔ خروج استیج ۵:** مجموعهٔ پایدار ویجت‌های عمومی و ووکامرس از طریق SDK ثبت شوند، فقط assetهای مصرف‌شده بارگذاری شوند و سناریوی کامل مشاهده محصول تا پرداخت و حساب کاربری آزموده شود.

---

## ترتیب انتشار پیشنهادی

| استیج | نسخهٔ هدف | خروجی اصلی |
|---|---:|---|
| ۱ | `0.1.0-rc1` | قالب React نصب‌شونده و زیرساخت Build |
| ۲ | `0.2.0-rc1` | پنل تنظیمات Schema-driven |
| ۳ | `0.3.0-rc1` | هدر و فوتر ساز |
| ۴ | `0.4.0-rc1` | صفحه‌ساز React |
| ۵ | `0.5.0-rc1` | بستهٔ ویجت‌های عمومی و ووکامرس |

## تعریف Done برای هر ورک

هر ورک زمانی Done است که پیاده‌سازی آن در شاخهٔ استیج قرار گرفته باشد، Build موفق باشد، معیار پذیرش نوشته‌شده اجرا شود، تغییرات Schema دارای migration باشد، مستندات مرتبط به‌روز شود و هیچ خطای بحرانی PHP/JavaScript یا regression شناخته‌شده باقی نماند.
