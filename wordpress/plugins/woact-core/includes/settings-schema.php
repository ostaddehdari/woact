<?php
/**
 * Versioned settings schema used by the dashboard and server validation.
 *
 * @package Woact_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    'version' => 2,
    'groups' => [
        [
            'id' => 'general',
            'label' => 'عمومی',
            'icon' => 'dashicons-admin-generic',
            'sections' => [
                [
                    'id' => 'render',
                    'label' => 'نحوهٔ رندر',
                    'description' => 'انتقال تدریجی صفحات وردپرس به React را کنترل می‌کند.',
                    'fields' => [
                        [
                            'id' => 'render.mode',
                            'label' => 'حالت رندر سایت',
                            'type' => 'button_group',
                            'default' => 'hybrid',
                            'options' => [
                                ['value' => 'classic', 'label' => 'وردپرس و صفحه‌سازها'],
                                ['value' => 'hybrid', 'label' => 'ترکیبی'],
                                ['value' => 'react', 'label' => 'React سراسری'],
                            ],
                        ],
                        [
                            'id' => 'render.preserve_builders',
                            'label' => 'حفاظت از صفحه‌سازها',
                            'type' => 'switch',
                            'default' => true,
                            'help' => 'صفحات Elementor، Divi، WPBakery، Bricks و سایر صفحه‌سازها با خروجی اصلی خودشان باز می‌شوند.',
                        ],
                        [
                            'id' => 'render.post_types',
                            'label' => 'انواع محتوای React',
                            'type' => 'post_types',
                            'default' => ['page', 'post'],
                            'depends' => ['field' => 'render.mode', 'operator' => 'equals', 'value' => 'react'],
                        ],
                    ],
                ],
                [
                    'id' => 'layout',
                    'label' => 'چیدمان سایت',
                    'fields' => [
                        [
                            'id' => 'layout.mode',
                            'label' => 'نوع چیدمان',
                            'type' => 'button_group',
                            'default' => 'wide',
                            'options' => [
                                ['value' => 'wide', 'label' => 'تمام‌عرض'],
                                ['value' => 'boxed', 'label' => 'جعبه‌ای'],
                            ],
                        ],
                        ['id' => 'layout.container_width', 'label' => 'عرض محتوای اصلی', 'type' => 'range', 'default' => 1180, 'min' => 960, 'max' => 1600, 'step' => 10, 'suffix' => 'px'],
                        ['id' => 'layout.content_width', 'label' => 'عرض محتوای نوشته', 'type' => 'range', 'default' => 760, 'min' => 560, 'max' => 1100, 'step' => 10, 'suffix' => 'px'],
                        ['id' => 'layout.site_background', 'label' => 'رنگ پس‌زمینه سایت', 'type' => 'color', 'default' => '#f8fafc'],
                        ['id' => 'layout.card_radius', 'label' => 'گردی عمومی کادرها', 'type' => 'range', 'default' => 14, 'min' => 0, 'max' => 40, 'step' => 1, 'suffix' => 'px'],
                        [
                            'id' => 'layout.direction',
                            'label' => 'جهت نمایش',
                            'type' => 'select',
                            'default' => 'auto',
                            'options' => [
                                ['value' => 'auto', 'label' => 'خودکار بر اساس زبان'],
                                ['value' => 'rtl', 'label' => 'راست‌چین'],
                                ['value' => 'ltr', 'label' => 'چپ‌چین'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'id' => 'notices',
            'label' => 'بنرها و اعلان‌ها',
            'icon' => 'dashicons-megaphone',
            'sections' => [
                [
                    'id' => 'top_banner',
                    'label' => 'بنر بالای سایت',
                    'fields' => [
                        ['id' => 'banner.enabled', 'label' => 'فعال‌سازی بنر', 'type' => 'switch', 'default' => false],
                        ['id' => 'banner.text', 'label' => 'متن بنر', 'type' => 'text', 'default' => '', 'depends' => ['field' => 'banner.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'banner.link', 'label' => 'پیوند بنر', 'type' => 'url', 'default' => '', 'depends' => ['field' => 'banner.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'banner.background', 'label' => 'رنگ پس‌زمینه', 'type' => 'color', 'default' => '#0f766e', 'depends' => ['field' => 'banner.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'banner.text_color', 'label' => 'رنگ نوشته', 'type' => 'color', 'default' => '#ffffff', 'depends' => ['field' => 'banner.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'banner.closable', 'label' => 'امکان بستن بنر', 'type' => 'switch', 'default' => true, 'depends' => ['field' => 'banner.enabled', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
                [
                    'id' => 'popup',
                    'label' => 'پنجره تبلیغاتی',
                    'fields' => [
                        ['id' => 'popup.enabled', 'label' => 'فعال‌سازی پنجره', 'type' => 'switch', 'default' => false],
                        ['id' => 'popup.title', 'label' => 'عنوان', 'type' => 'text', 'default' => '', 'depends' => ['field' => 'popup.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'popup.content', 'label' => 'محتوا', 'type' => 'textarea', 'default' => '', 'depends' => ['field' => 'popup.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'popup.image', 'label' => 'تصویر', 'type' => 'media', 'default' => '', 'depends' => ['field' => 'popup.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'popup.delay', 'label' => 'تأخیر نمایش', 'type' => 'number', 'default' => 3, 'min' => 0, 'max' => 120, 'suffix' => 'ثانیه', 'depends' => ['field' => 'popup.enabled', 'operator' => 'equals', 'value' => true]],
                        [
                            'id' => 'popup.frequency',
                            'label' => 'تکرار نمایش',
                            'type' => 'select',
                            'default' => 'session',
                            'options' => [
                                ['value' => 'always', 'label' => 'هر بار'],
                                ['value' => 'session', 'label' => 'هر نشست'],
                                ['value' => 'day', 'label' => 'روزی یک بار'],
                                ['value' => 'week', 'label' => 'هفته‌ای یک بار'],
                            ],
                            'depends' => ['field' => 'popup.enabled', 'operator' => 'equals', 'value' => true],
                        ],
                    ],
                ],
                [
                    'id' => 'consent',
                    'label' => 'تأیید سن و کوکی',
                    'fields' => [
                        ['id' => 'age.enabled', 'label' => 'تأیید سن', 'type' => 'switch', 'default' => false],
                        ['id' => 'age.minimum', 'label' => 'حداقل سن', 'type' => 'number', 'default' => 18, 'min' => 1, 'max' => 99, 'depends' => ['field' => 'age.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'age.message', 'label' => 'پیام تأیید سن', 'type' => 'textarea', 'default' => 'برای ورود، سن خود را تأیید کنید.', 'depends' => ['field' => 'age.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'cookie.enabled', 'label' => 'اعلان کوکی', 'type' => 'switch', 'default' => false],
                        ['id' => 'cookie.message', 'label' => 'متن اعلان کوکی', 'type' => 'textarea', 'default' => 'این سایت برای بهبود تجربه شما از کوکی استفاده می‌کند.', 'depends' => ['field' => 'cookie.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'cookie.policy_url', 'label' => 'پیوند حریم خصوصی', 'type' => 'url', 'default' => '', 'depends' => ['field' => 'cookie.enabled', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
            ],
        ],
        [
            'id' => 'navigation',
            'label' => 'جست‌وجو و ناوبری',
            'icon' => 'dashicons-menu-alt3',
            'sections' => [
                [
                    'id' => 'search',
                    'label' => 'جست‌وجو',
                    'fields' => [
                        ['id' => 'search.live', 'label' => 'جست‌وجوی زنده', 'type' => 'switch', 'default' => true],
                        ['id' => 'search.minimum_chars', 'label' => 'حداقل حروف جست‌وجو', 'type' => 'number', 'default' => 3, 'min' => 1, 'max' => 10, 'depends' => ['field' => 'search.live', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'search.post_types', 'label' => 'انواع محتوای قابل جست‌وجو', 'type' => 'post_types', 'default' => ['post', 'page', 'product']],
                        ['id' => 'search.show_images', 'label' => 'نمایش تصویر در نتایج', 'type' => 'switch', 'default' => true],
                        ['id' => 'search.show_price', 'label' => 'نمایش قیمت محصول', 'type' => 'switch', 'default' => true],
                    ],
                ],
                [
                    'id' => 'mobile_bar',
                    'label' => 'نوار موبایل',
                    'fields' => [
                        ['id' => 'mobile_bar.enabled', 'label' => 'فعال‌سازی نوار موبایل', 'type' => 'switch', 'default' => true],
                        [
                            'id' => 'mobile_bar.items',
                            'label' => 'آیتم‌های نوار',
                            'type' => 'sortable',
                            'default' => ['home', 'search', 'account', 'wishlist', 'cart'],
                            'options' => [
                                ['value' => 'home', 'label' => 'خانه'],
                                ['value' => 'search', 'label' => 'جست‌وجو'],
                                ['value' => 'account', 'label' => 'حساب'],
                                ['value' => 'wishlist', 'label' => 'علاقه‌مندی'],
                                ['value' => 'cart', 'label' => 'سبد خرید'],
                            ],
                            'depends' => ['field' => 'mobile_bar.enabled', 'operator' => 'equals', 'value' => true],
                        ],
                        ['id' => 'navigation.sticky', 'label' => 'پیمایش چسبان', 'type' => 'switch', 'default' => true],
                        ['id' => 'navigation.scroll_top', 'label' => 'دکمه بازگشت به بالا', 'type' => 'switch', 'default' => true],
                    ],
                ],
            ],
        ],
        [
            'id' => 'page_title',
            'label' => 'عنوان صفحه',
            'icon' => 'dashicons-editor-textcolor',
            'sections' => [
                [
                    'id' => 'page_title_style',
                    'label' => 'نمایش عنوان و مسیر',
                    'fields' => [
                        ['id' => 'page_title.enabled', 'label' => 'نمایش عنوان صفحه', 'type' => 'switch', 'default' => true],
                        ['id' => 'page_title.breadcrumbs', 'label' => 'نمایش مسیر راهنما', 'type' => 'switch', 'default' => true],
                        [
                            'id' => 'page_title.align',
                            'label' => 'چیدمان عنوان',
                            'type' => 'button_group',
                            'default' => 'center',
                            'options' => [
                                ['value' => 'start', 'label' => 'ابتدا'],
                                ['value' => 'center', 'label' => 'وسط'],
                                ['value' => 'end', 'label' => 'انتها'],
                            ],
                            'depends' => ['field' => 'page_title.enabled', 'operator' => 'equals', 'value' => true],
                        ],
                        ['id' => 'page_title.height', 'label' => 'ارتفاع', 'type' => 'range', 'default' => 220, 'min' => 80, 'max' => 600, 'step' => 10, 'suffix' => 'px', 'depends' => ['field' => 'page_title.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'page_title.background', 'label' => 'رنگ پس‌زمینه', 'type' => 'color', 'default' => '#e8f5f3', 'depends' => ['field' => 'page_title.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'page_title.background_image', 'label' => 'تصویر پس‌زمینه', 'type' => 'media', 'default' => '', 'depends' => ['field' => 'page_title.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'page_title.overlay', 'label' => 'تیرگی تصویر', 'type' => 'range', 'default' => 0, 'min' => 0, 'max' => 90, 'suffix' => '%', 'depends' => ['field' => 'page_title.enabled', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
            ],
        ],
        [
            'id' => 'footer',
            'label' => 'پاورقی',
            'icon' => 'dashicons-table-row-after',
            'sections' => [
                [
                    'id' => 'footer_layout',
                    'label' => 'چیدمان و ظاهر',
                    'fields' => [
                        ['id' => 'footer.enabled', 'label' => 'نمایش پاورقی', 'type' => 'switch', 'default' => true],
                        ['id' => 'footer.columns', 'label' => 'تعداد ستون‌ها', 'type' => 'range', 'default' => 4, 'min' => 1, 'max' => 6, 'depends' => ['field' => 'footer.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'footer.background', 'label' => 'رنگ پس‌زمینه', 'type' => 'color', 'default' => '#0f172a', 'depends' => ['field' => 'footer.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'footer.text_color', 'label' => 'رنگ نوشته', 'type' => 'color', 'default' => '#e2e8f0', 'depends' => ['field' => 'footer.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'footer.prefooter_enabled', 'label' => 'نمایش پیش‌پاورقی', 'type' => 'switch', 'default' => false],
                        ['id' => 'footer.prefooter_content', 'label' => 'محتوای پیش‌پاورقی', 'type' => 'textarea', 'default' => '', 'depends' => ['field' => 'footer.prefooter_enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'footer.copyright', 'label' => 'متن حق نشر', 'type' => 'text', 'default' => '© {year} {site_name}', 'help' => 'متغیرهای {year} و {site_name} قابل استفاده‌اند.'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'typography',
            'label' => 'تایپوگرافی',
            'icon' => 'dashicons-editor-spellcheck',
            'sections' => [
                [
                    'id' => 'fonts',
                    'label' => 'فونت‌های عمومی',
                    'fields' => [
                        [
                            'id' => 'typography.body',
                            'label' => 'فونت متن',
                            'type' => 'typography',
                            'default' => ['family' => 'Tahoma', 'size' => 16, 'weight' => '400', 'lineHeight' => 1.8],
                        ],
                        [
                            'id' => 'typography.headings',
                            'label' => 'فونت عنوان‌ها',
                            'type' => 'typography',
                            'default' => ['family' => 'Tahoma', 'size' => 32, 'weight' => '700', 'lineHeight' => 1.4],
                        ],
                        ['id' => 'typography.custom_font_url', 'label' => 'نشانی فایل فونت سفارشی', 'type' => 'url', 'default' => ''],
                        ['id' => 'typography.custom_font_name', 'label' => 'نام فونت سفارشی', 'type' => 'text', 'default' => '', 'depends' => ['field' => 'typography.custom_font_url', 'operator' => 'not_equals', 'value' => '']],
                        ['id' => 'typography.icon_font_url', 'label' => 'فونت آیکون سفارشی', 'type' => 'url', 'default' => ''],
                        ['id' => 'typography.font_display', 'label' => 'نحوه بارگذاری فونت', 'type' => 'select', 'default' => 'swap', 'options' => [
                            ['value' => 'swap', 'label' => 'Swap'],
                            ['value' => 'optional', 'label' => 'Optional'],
                            ['value' => 'block', 'label' => 'Block'],
                        ]],
                    ],
                ],
            ],
        ],
        [
            'id' => 'styles',
            'label' => 'استایل‌ها و رنگ‌ها',
            'icon' => 'dashicons-art',
            'sections' => [
                [
                    'id' => 'colors',
                    'label' => 'رنگ‌های سراسری',
                    'fields' => [
                        ['id' => 'style.primary', 'label' => 'رنگ اصلی', 'type' => 'color', 'default' => '#0f766e'],
                        ['id' => 'style.accent', 'label' => 'رنگ مکمل', 'type' => 'color', 'default' => '#f59e0b'],
                        ['id' => 'style.text', 'label' => 'رنگ متن', 'type' => 'color', 'default' => '#17202a'],
                        ['id' => 'style.muted', 'label' => 'رنگ متن کم‌رنگ', 'type' => 'color', 'default' => '#64748b'],
                        ['id' => 'style.border', 'label' => 'رنگ کادر', 'type' => 'color', 'default' => '#e2e8f0'],
                        ['id' => 'style.link', 'label' => 'رنگ پیوند', 'type' => 'color', 'default' => '#0f766e'],
                        ['id' => 'style.gradient', 'label' => 'گرادیان اصلی', 'type' => 'gradient', 'default' => 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)'],
                    ],
                ],
                [
                    'id' => 'controls',
                    'label' => 'دکمه و فرم',
                    'fields' => [
                        ['id' => 'style.button_radius', 'label' => 'گردی دکمه‌ها', 'type' => 'range', 'default' => 10, 'min' => 0, 'max' => 40, 'suffix' => 'px'],
                        ['id' => 'style.input_radius', 'label' => 'گردی فیلدها', 'type' => 'range', 'default' => 8, 'min' => 0, 'max' => 40, 'suffix' => 'px'],
                        ['id' => 'style.button_uppercase', 'label' => 'حروف بزرگ دکمه انگلیسی', 'type' => 'switch', 'default' => false],
                        ['id' => 'style.focus_ring', 'label' => 'نمایش واضح Focus', 'type' => 'switch', 'default' => true],
                    ],
                ],
            ],
        ],
        [
            'id' => 'blog',
            'label' => 'وبلاگ',
            'icon' => 'dashicons-admin-post',
            'sections' => [
                [
                    'id' => 'blog_archive',
                    'label' => 'آرشیو نوشته‌ها',
                    'fields' => [
                        ['id' => 'blog.layout', 'label' => 'چیدمان آرشیو', 'type' => 'select', 'default' => 'grid', 'options' => [
                            ['value' => 'grid', 'label' => 'شبکه‌ای'],
                            ['value' => 'list', 'label' => 'فهرستی'],
                            ['value' => 'masonry', 'label' => 'آجرچین'],
                        ]],
                        ['id' => 'blog.columns', 'label' => 'تعداد ستون', 'type' => 'range', 'default' => 3, 'min' => 1, 'max' => 4, 'depends' => ['field' => 'blog.layout', 'operator' => 'contains', 'value' => ['grid', 'masonry']]],
                        ['id' => 'blog.excerpt_length', 'label' => 'طول خلاصه', 'type' => 'number', 'default' => 34, 'min' => 0, 'max' => 200, 'suffix' => 'کلمه'],
                        ['id' => 'blog.show_date', 'label' => 'نمایش تاریخ', 'type' => 'switch', 'default' => true],
                        ['id' => 'blog.show_author', 'label' => 'نمایش نویسنده', 'type' => 'switch', 'default' => true],
                        ['id' => 'blog.pagination', 'label' => 'صفحه‌بندی', 'type' => 'select', 'default' => 'numbers', 'options' => [
                            ['value' => 'numbers', 'label' => 'شماره‌ای'],
                            ['value' => 'load_more', 'label' => 'بارگذاری بیشتر'],
                            ['value' => 'infinite', 'label' => 'بی‌نهایت'],
                        ]],
                    ],
                ],
                [
                    'id' => 'blog_single',
                    'label' => 'نوشته تکی',
                    'fields' => [
                        ['id' => 'blog.single_sidebar', 'label' => 'ستون کناری', 'type' => 'select', 'default' => 'none', 'options' => [
                            ['value' => 'none', 'label' => 'بدون ستون'],
                            ['value' => 'right', 'label' => 'راست'],
                            ['value' => 'left', 'label' => 'چپ'],
                        ]],
                        ['id' => 'blog.author_box', 'label' => 'جعبه نویسنده', 'type' => 'switch', 'default' => true],
                        ['id' => 'blog.share', 'label' => 'دکمه‌های اشتراک‌گذاری', 'type' => 'switch', 'default' => true],
                        ['id' => 'blog.related', 'label' => 'نوشته‌های مرتبط', 'type' => 'switch', 'default' => true],
                        ['id' => 'blog.related_count', 'label' => 'تعداد نوشته مرتبط', 'type' => 'number', 'default' => 3, 'min' => 1, 'max' => 12, 'depends' => ['field' => 'blog.related', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
            ],
        ],
        [
            'id' => 'portfolio',
            'label' => 'نمونه‌کار',
            'icon' => 'dashicons-portfolio',
            'sections' => [
                [
                    'id' => 'portfolio_general',
                    'label' => 'تنظیمات نمونه‌کار',
                    'fields' => [
                        ['id' => 'portfolio.enabled', 'label' => 'فعال‌سازی نمونه‌کار', 'type' => 'switch', 'default' => false],
                        ['id' => 'portfolio.slug', 'label' => 'نامک آرشیو', 'type' => 'text', 'default' => 'portfolio', 'depends' => ['field' => 'portfolio.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'portfolio.columns', 'label' => 'تعداد ستون‌ها', 'type' => 'range', 'default' => 3, 'min' => 1, 'max' => 6, 'depends' => ['field' => 'portfolio.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'portfolio.filters', 'label' => 'فیلتر دسته‌بندی', 'type' => 'switch', 'default' => true, 'depends' => ['field' => 'portfolio.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'portfolio.lightbox', 'label' => 'نمایش تصویر در Lightbox', 'type' => 'switch', 'default' => true, 'depends' => ['field' => 'portfolio.enabled', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
            ],
        ],
        [
            'id' => 'shop',
            'label' => 'فروشگاه',
            'icon' => 'dashicons-store',
            'sections' => [
                [
                    'id' => 'shop_general',
                    'label' => 'عمومی فروشگاه',
                    'fields' => [
                        ['id' => 'shop.products_per_page', 'label' => 'محصول در هر صفحه', 'type' => 'number', 'default' => 12, 'min' => 1, 'max' => 100],
                        ['id' => 'shop.columns_desktop', 'label' => 'ستون دسکتاپ', 'type' => 'range', 'default' => 4, 'min' => 2, 'max' => 6],
                        ['id' => 'shop.columns_tablet', 'label' => 'ستون تبلت', 'type' => 'range', 'default' => 3, 'min' => 1, 'max' => 4],
                        ['id' => 'shop.columns_mobile', 'label' => 'ستون موبایل', 'type' => 'range', 'default' => 2, 'min' => 1, 'max' => 2],
                        ['id' => 'shop.quick_view', 'label' => 'نمایش سریع', 'type' => 'switch', 'default' => true],
                        ['id' => 'shop.compare', 'label' => 'مقایسه محصولات', 'type' => 'switch', 'default' => true],
                        ['id' => 'shop.wishlist', 'label' => 'علاقه‌مندی‌ها', 'type' => 'switch', 'default' => true],
                        ['id' => 'shop.waitlist', 'label' => 'لیست انتظار موجودی', 'type' => 'switch', 'default' => false],
                        ['id' => 'shop.brands', 'label' => 'برند محصولات', 'type' => 'switch', 'default' => true],
                        ['id' => 'shop.sale_badge', 'label' => 'برچسب تخفیف', 'type' => 'select', 'default' => 'percentage', 'options' => [
                            ['value' => 'text', 'label' => 'متن تخفیف'],
                            ['value' => 'percentage', 'label' => 'درصد تخفیف'],
                            ['value' => 'amount', 'label' => 'مبلغ تخفیف'],
                        ]],
                    ],
                ],
                [
                    'id' => 'variable_products',
                    'label' => 'محصول متغیر',
                    'fields' => [
                        ['id' => 'shop.swatches', 'label' => 'نمونه رنگ و تصویر', 'type' => 'switch', 'default' => true],
                        ['id' => 'shop.swatches_archive', 'label' => 'نمونه‌ها در آرشیو', 'type' => 'switch', 'default' => true, 'depends' => ['field' => 'shop.swatches', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'shop.hide_out_of_stock_variations', 'label' => 'مخفی‌کردن تنوع ناموجود', 'type' => 'switch', 'default' => true],
                        ['id' => 'shop.variation_gallery', 'label' => 'گالری مستقل هر تنوع', 'type' => 'switch', 'default' => true],
                    ],
                ],
            ],
        ],
        [
            'id' => 'catalog',
            'label' => 'آرشیو محصول',
            'icon' => 'dashicons-screenoptions',
            'sections' => [
                [
                    'id' => 'catalog_layout',
                    'label' => 'شبکه و فیلترها',
                    'fields' => [
                        ['id' => 'catalog.sidebar', 'label' => 'ستون فیلترها', 'type' => 'select', 'default' => 'right', 'options' => [
                            ['value' => 'none', 'label' => 'بدون ستون'],
                            ['value' => 'right', 'label' => 'راست'],
                            ['value' => 'left', 'label' => 'چپ'],
                            ['value' => 'offcanvas', 'label' => 'کشویی'],
                        ]],
                        ['id' => 'catalog.sticky_sidebar', 'label' => 'فیلتر چسبان', 'type' => 'switch', 'default' => true, 'depends' => ['field' => 'catalog.sidebar', 'operator' => 'contains', 'value' => ['right', 'left']]],
                        ['id' => 'catalog.ajax_filters', 'label' => 'فیلتر AJAX', 'type' => 'switch', 'default' => true],
                        ['id' => 'catalog.active_filters', 'label' => 'نمایش فیلترهای فعال', 'type' => 'switch', 'default' => true],
                        ['id' => 'catalog.hover', 'label' => 'حالت کارت محصول', 'type' => 'select', 'default' => 'buttons', 'options' => [
                            ['value' => 'none', 'label' => 'ساده'],
                            ['value' => 'buttons', 'label' => 'دکمه‌های شناور'],
                            ['value' => 'swap', 'label' => 'تعویض تصویر'],
                        ]],
                        ['id' => 'catalog.image_ratio', 'label' => 'نسبت تصویر', 'type' => 'select', 'default' => '1/1', 'options' => [
                            ['value' => '1/1', 'label' => 'مربع'],
                            ['value' => '4/5', 'label' => 'عمودی'],
                            ['value' => '3/2', 'label' => 'افقی'],
                            ['value' => 'auto', 'label' => 'خودکار'],
                        ]],
                    ],
                ],
            ],
        ],
        [
            'id' => 'product',
            'label' => 'محصول تکی',
            'icon' => 'dashicons-products',
            'sections' => [
                [
                    'id' => 'product_layout',
                    'label' => 'چیدمان محصول',
                    'fields' => [
                        ['id' => 'product.gallery', 'label' => 'چیدمان گالری', 'type' => 'select', 'default' => 'thumbnails', 'options' => [
                            ['value' => 'thumbnails', 'label' => 'تصویر با بندانگشتی'],
                            ['value' => 'grid', 'label' => 'شبکه‌ای'],
                            ['value' => 'carousel', 'label' => 'اسلایدر'],
                            ['value' => 'stacked', 'label' => 'پشت سرهم'],
                        ]],
                        ['id' => 'product.zoom', 'label' => 'بزرگ‌نمایی تصویر', 'type' => 'switch', 'default' => true],
                        ['id' => 'product.lightbox', 'label' => 'گالری Lightbox', 'type' => 'switch', 'default' => true],
                        ['id' => 'product.sticky_summary', 'label' => 'اطلاعات چسبان', 'type' => 'switch', 'default' => true],
                        ['id' => 'product.buy_now', 'label' => 'دکمه خرید فوری', 'type' => 'switch', 'default' => true],
                        ['id' => 'product.tabs_layout', 'label' => 'نمایش اطلاعات محصول', 'type' => 'select', 'default' => 'tabs', 'options' => [
                            ['value' => 'tabs', 'label' => 'تب'],
                            ['value' => 'accordion', 'label' => 'آکاردئون'],
                            ['value' => 'sections', 'label' => 'بخش‌های پیوسته'],
                        ]],
                        ['id' => 'product.reviews', 'label' => 'دیدگاه محصول', 'type' => 'switch', 'default' => true],
                        ['id' => 'product.related', 'label' => 'محصولات مرتبط', 'type' => 'switch', 'default' => true],
                        ['id' => 'product.related_count', 'label' => 'تعداد محصول مرتبط', 'type' => 'number', 'default' => 4, 'min' => 1, 'max' => 12, 'depends' => ['field' => 'product.related', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'product.live_viewers', 'label' => 'نمایش شمارنده بازدیدکنندگان', 'type' => 'switch', 'default' => false],
                        ['id' => 'product.sold_counter', 'label' => 'نمایش شمارنده فروش', 'type' => 'switch', 'default' => false],
                    ],
                ],
            ],
        ],
        [
            'id' => 'commerce_tools',
            'label' => 'ابزارهای فروش',
            'icon' => 'dashicons-chart-line',
            'sections' => [
                [
                    'id' => 'sales_tools',
                    'label' => 'افزایش فروش',
                    'fields' => [
                        ['id' => 'sales.frequently_bought', 'label' => 'خرید هم‌زمان محصولات', 'type' => 'switch', 'default' => false],
                        ['id' => 'sales.gifts', 'label' => 'هدیه خرید', 'type' => 'switch', 'default' => false],
                        ['id' => 'sales.free_shipping_bar', 'label' => 'نوار ارسال رایگان', 'type' => 'switch', 'default' => true],
                        ['id' => 'sales.free_shipping_threshold', 'label' => 'حد ارسال رایگان', 'type' => 'number', 'default' => 0, 'min' => 0, 'depends' => ['field' => 'sales.free_shipping_bar', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'sales.delivery_estimate', 'label' => 'تخمین زمان تحویل', 'type' => 'switch', 'default' => false],
                        ['id' => 'sales.delivery_days', 'label' => 'بازه تحویل', 'type' => 'text', 'default' => '۲ تا ۵ روز کاری', 'depends' => ['field' => 'sales.delivery_estimate', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'sales.abandoned_cart', 'label' => 'بازیابی سبد رهاشده', 'type' => 'switch', 'default' => false],
                        ['id' => 'sales.price_tracker', 'label' => 'اطلاع کاهش قیمت', 'type' => 'switch', 'default' => false],
                    ],
                ],
            ],
        ],
        [
            'id' => 'checkout',
            'label' => 'سبد و پرداخت',
            'icon' => 'dashicons-cart',
            'sections' => [
                [
                    'id' => 'cart',
                    'label' => 'سبد خرید',
                    'fields' => [
                        ['id' => 'cart.drawer', 'label' => 'سبد خرید کشویی', 'type' => 'switch', 'default' => true],
                        ['id' => 'cart.ajax_add', 'label' => 'افزودن AJAX به سبد', 'type' => 'switch', 'default' => true],
                        ['id' => 'cart.cross_sells', 'label' => 'پیشنهاد مکمل', 'type' => 'switch', 'default' => true],
                        ['id' => 'cart.quantity_updates', 'label' => 'به‌روزرسانی خودکار تعداد', 'type' => 'switch', 'default' => true],
                    ],
                ],
                [
                    'id' => 'checkout_page',
                    'label' => 'تسویه‌حساب و تشکر',
                    'fields' => [
                        ['id' => 'checkout.layout', 'label' => 'چیدمان پرداخت', 'type' => 'select', 'default' => 'two_columns', 'options' => [
                            ['value' => 'one_column', 'label' => 'یک ستون'],
                            ['value' => 'two_columns', 'label' => 'دو ستون'],
                            ['value' => 'steps', 'label' => 'مرحله‌ای'],
                        ]],
                        ['id' => 'checkout.distraction_free', 'label' => 'حذف عوامل حواس‌پرتی', 'type' => 'switch', 'default' => false],
                        ['id' => 'checkout.coupon', 'label' => 'نمایش کد تخفیف', 'type' => 'switch', 'default' => true],
                        ['id' => 'checkout.order_notes', 'label' => 'یادداشت سفارش', 'type' => 'switch', 'default' => true],
                        ['id' => 'checkout.thankyou_message', 'label' => 'پیام صفحه تشکر', 'type' => 'textarea', 'default' => 'سفارش شما با موفقیت ثبت شد.'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'account',
            'label' => 'حساب کاربری',
            'icon' => 'dashicons-admin-users',
            'sections' => [
                [
                    'id' => 'account_layout',
                    'label' => 'ورود و داشبورد',
                    'fields' => [
                        ['id' => 'account.login_modal', 'label' => 'ورود در پنجره بازشو', 'type' => 'switch', 'default' => false],
                        ['id' => 'account.dashboard_layout', 'label' => 'چیدمان داشبورد', 'type' => 'select', 'default' => 'sidebar', 'options' => [
                            ['value' => 'sidebar', 'label' => 'منوی کناری'],
                            ['value' => 'cards', 'label' => 'کارت‌ها'],
                            ['value' => 'tabs', 'label' => 'تب‌ها'],
                        ]],
                        ['id' => 'account.avatar', 'label' => 'نمایش تصویر کاربر', 'type' => 'switch', 'default' => true],
                        ['id' => 'account.downloads', 'label' => 'نمایش دانلودها', 'type' => 'switch', 'default' => true],
                        ['id' => 'account.orders', 'label' => 'نمایش سفارش‌ها', 'type' => 'switch', 'default' => true],
                        ['id' => 'account.wishlist', 'label' => 'نمایش علاقه‌مندی‌ها', 'type' => 'switch', 'default' => true],
                    ],
                ],
            ],
        ],
        [
            'id' => 'social',
            'label' => 'شبکه‌های اجتماعی',
            'icon' => 'dashicons-share',
            'sections' => [
                [
                    'id' => 'social_profiles',
                    'label' => 'پروفایل‌ها',
                    'fields' => [
                        [
                            'id' => 'social.profiles',
                            'label' => 'پیوند شبکه‌ها',
                            'type' => 'repeater',
                            'default' => [],
                            'columns' => [
                                ['id' => 'network', 'label' => 'شبکه', 'type' => 'text'],
                                ['id' => 'url', 'label' => 'نشانی', 'type' => 'url'],
                                ['id' => 'icon', 'label' => 'آیکون', 'type' => 'icon'],
                            ],
                        ],
                        [
                            'id' => 'social.share_networks',
                            'label' => 'شبکه‌های اشتراک‌گذاری',
                            'type' => 'multiselect',
                            'default' => ['telegram', 'whatsapp', 'x', 'linkedin'],
                            'options' => [
                                ['value' => 'telegram', 'label' => 'Telegram'],
                                ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                                ['value' => 'x', 'label' => 'X'],
                                ['value' => 'linkedin', 'label' => 'LinkedIn'],
                                ['value' => 'facebook', 'label' => 'Facebook'],
                                ['value' => 'pinterest', 'label' => 'Pinterest'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'id' => 'integrations',
            'label' => 'API و یکپارچه‌سازی',
            'icon' => 'dashicons-rest-api',
            'sections' => [
                [
                    'id' => 'external_services',
                    'label' => 'سرویس‌های خارجی',
                    'fields' => [
                        ['id' => 'integrations.google_maps_key', 'label' => 'کلید Google Maps', 'type' => 'secret', 'default' => ''],
                        ['id' => 'integrations.instagram_token', 'label' => 'توکن Instagram', 'type' => 'secret', 'default' => ''],
                        ['id' => 'integrations.google_login', 'label' => 'ورود با Google', 'type' => 'switch', 'default' => false],
                        ['id' => 'integrations.google_client_id', 'label' => 'Google Client ID', 'type' => 'text', 'default' => '', 'depends' => ['field' => 'integrations.google_login', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'integrations.google_client_secret', 'label' => 'Google Client Secret', 'type' => 'secret', 'default' => '', 'depends' => ['field' => 'integrations.google_login', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'integrations.analytics_id', 'label' => 'شناسه Analytics', 'type' => 'text', 'default' => ''],
                        ['id' => 'integrations.tag_manager_id', 'label' => 'شناسه Tag Manager', 'type' => 'text', 'default' => ''],
                    ],
                ],
            ],
        ],
        [
            'id' => 'performance',
            'label' => 'عملکرد',
            'icon' => 'dashicons-performance',
            'sections' => [
                [
                    'id' => 'assets',
                    'label' => 'CSS و JavaScript',
                    'fields' => [
                        ['id' => 'performance.minify_css', 'label' => 'فشرده‌سازی CSS تولیدی Woact', 'type' => 'switch', 'default' => true],
                        ['id' => 'performance.defer_js', 'label' => 'تأخیر JavaScript غیرضروری', 'type' => 'switch', 'default' => true],
                        ['id' => 'performance.conditional_assets', 'label' => 'بارگذاری فایل فقط در صفحات لازم', 'type' => 'switch', 'default' => true],
                        ['id' => 'performance.disable_emojis', 'label' => 'حذف Emojiهای وردپرس', 'type' => 'switch', 'default' => true],
                        ['id' => 'performance.disable_embeds', 'label' => 'حذف wp-embed اضافی', 'type' => 'switch', 'default' => false],
                    ],
                ],
                [
                    'id' => 'media_performance',
                    'label' => 'تصویر و فونت',
                    'fields' => [
                        ['id' => 'performance.lazy_images', 'label' => 'Lazy Load تصاویر', 'type' => 'switch', 'default' => true],
                        ['id' => 'performance.lazy_iframes', 'label' => 'Lazy Load iframe', 'type' => 'switch', 'default' => true],
                        ['id' => 'performance.preload_featured', 'label' => 'Preload تصویر شاخص', 'type' => 'switch', 'default' => true],
                        ['id' => 'performance.font_preload', 'label' => 'Preload فونت سفارشی', 'type' => 'switch', 'default' => false],
                        ['id' => 'performance.preloader', 'label' => 'پیش‌بارگذار صفحه', 'type' => 'switch', 'default' => false],
                    ],
                ],
            ],
        ],
        [
            'id' => 'maintenance',
            'label' => 'نگهداری و برند',
            'icon' => 'dashicons-hammer',
            'sections' => [
                [
                    'id' => 'maintenance_mode',
                    'label' => 'حالت تعمیر',
                    'fields' => [
                        ['id' => 'maintenance.enabled', 'label' => 'فعال‌سازی حالت تعمیر', 'type' => 'switch', 'default' => false],
                        ['id' => 'maintenance.title', 'label' => 'عنوان', 'type' => 'text', 'default' => 'به‌زودی بازمی‌گردیم', 'depends' => ['field' => 'maintenance.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'maintenance.message', 'label' => 'پیام', 'type' => 'textarea', 'default' => 'در حال به‌روزرسانی سایت هستیم.', 'depends' => ['field' => 'maintenance.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'maintenance.image', 'label' => 'تصویر', 'type' => 'media', 'default' => '', 'depends' => ['field' => 'maintenance.enabled', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
                [
                    'id' => 'white_label',
                    'label' => 'برند اختصاصی',
                    'fields' => [
                        ['id' => 'white_label.enabled', 'label' => 'فعال‌سازی White Label', 'type' => 'switch', 'default' => false],
                        ['id' => 'white_label.name', 'label' => 'نام نمایشی قالب', 'type' => 'text', 'default' => 'Woact', 'depends' => ['field' => 'white_label.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'white_label.logo', 'label' => 'لوگوی پیشخوان', 'type' => 'media', 'default' => '', 'depends' => ['field' => 'white_label.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'white_label.hide_version', 'label' => 'مخفی‌کردن نسخه', 'type' => 'switch', 'default' => false, 'depends' => ['field' => 'white_label.enabled', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
            ],
        ],
        [
            'id' => 'custom_code',
            'label' => 'کد سفارشی',
            'icon' => 'dashicons-editor-code',
            'sections' => [
                [
                    'id' => 'custom_assets',
                    'label' => 'CSS و JavaScript سفارشی',
                    'description' => 'فقط مدیرانی که به کد اعتماد دارند باید این بخش را فعال کنند.',
                    'fields' => [
                        ['id' => 'custom.enabled', 'label' => 'اجرای کد سفارشی', 'type' => 'switch', 'default' => false],
                        ['id' => 'custom.css', 'label' => 'CSS سفارشی', 'type' => 'code', 'language' => 'css', 'default' => '', 'depends' => ['field' => 'custom.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'custom.js_head', 'label' => 'JavaScript در Head', 'type' => 'code', 'language' => 'javascript', 'default' => '', 'depends' => ['field' => 'custom.enabled', 'operator' => 'equals', 'value' => true]],
                        ['id' => 'custom.js_footer', 'label' => 'JavaScript در Footer', 'type' => 'code', 'language' => 'javascript', 'default' => '', 'depends' => ['field' => 'custom.enabled', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
            ],
        ],
        [
            'id' => 'tools',
            'label' => 'درون‌ریزی و ابزارها',
            'icon' => 'dashicons-database',
            'sections' => [
                [
                    'id' => 'data_tools',
                    'label' => 'مدیریت تنظیمات',
                    'description' => 'Preset، خروجی، درون‌ریزی، بازنشانی و تاریخچه از نوار ابزار این صفحه در دسترس‌اند.',
                    'fields' => [
                        ['id' => 'tools.keep_revisions', 'label' => 'تعداد نسخه‌های نگهداری‌شده', 'type' => 'number', 'default' => 20, 'min' => 5, 'max' => 100],
                        ['id' => 'tools.autosave_draft', 'label' => 'ذخیره خودکار پیش‌نویس', 'type' => 'switch', 'default' => true],
                        ['id' => 'tools.autosave_delay', 'label' => 'فاصله ذخیره خودکار', 'type' => 'number', 'default' => 2, 'min' => 1, 'max' => 30, 'suffix' => 'ثانیه', 'depends' => ['field' => 'tools.autosave_draft', 'operator' => 'equals', 'value' => true]],
                    ],
                ],
            ],
        ],
    ],
];

