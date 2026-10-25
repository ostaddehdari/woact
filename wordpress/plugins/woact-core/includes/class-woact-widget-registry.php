<?php
declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WOACT_TESTING')) exit;

/** Extensible registry and lazy data resolver for Woact page widgets. */
final class Woact_Widget_Registry
{
    private array $definitions = [];
    private array $categories = [];
    private array $resolvers = [];
    private array $packages = [];
    private bool $booted = false;

    public function boot(): void
    {
        if ($this->booted) return;
        $this->booted = true;
        foreach ($this->default_categories() as $category) $this->register_category($category);
        foreach ($this->default_definitions() as $definition) $this->register($definition);
        $this->discover_packages();
        if (function_exists('do_action')) do_action('woact_register_widgets', $this);
        if (function_exists('apply_filters')) {
            $filtered = apply_filters('woact_widget_definitions', $this->definitions, $this);
            if (is_array($filtered)) $this->definitions = $filtered;
        }
    }

    public function register(array $definition): bool
    {
        $type = sanitize_key((string) ($definition['type'] ?? ''));
        if ($type === '' || !preg_match('/^[a-z][a-z0-9_]{1,63}$/', $type)) return false;
        $definition['type'] = $type;
        $definition['label'] = sanitize_text_field((string) ($definition['label'] ?? $type));
        $definition['icon'] = sanitize_key((string) ($definition['icon'] ?? 'admin-generic'));
        $definition['category'] = sanitize_key((string) ($definition['category'] ?? 'general_widgets'));
        $definition['defaults'] = is_array($definition['defaults'] ?? null) ? $definition['defaults'] : [];
        $definition['fields'] = is_array($definition['fields'] ?? null) ? $definition['fields'] : [];
        $definition['container'] = !empty($definition['container']);
        $definition['dataGroup'] = sanitize_key((string) ($definition['dataGroup'] ?? ''));
        $definition['assets'] = is_array($definition['assets'] ?? null) ? $definition['assets'] : [];
        $definition['version'] = preg_replace('/[^0-9A-Za-z._-]/', '', (string) ($definition['version'] ?? '1.0.0')) ?: '1.0.0';
        $definition['package'] = sanitize_key((string) ($definition['package'] ?? 'core'));
        $definition['dependencies'] = array_values(array_filter(array_map('sanitize_key', (array) ($definition['dependencies'] ?? []))));
        $this->definitions[$type] = $definition;
        return true;
    }

    public function register_category(array $category): bool
    {
        $id = sanitize_key((string) ($category['id'] ?? ''));
        if ($id === '') return false;
        $this->categories[$id] = ['id' => $id, 'label' => sanitize_text_field((string) ($category['label'] ?? $id))];
        return true;
    }

    public function register_resolver(string $group, callable $resolver): bool
    {
        $group = sanitize_key($group);
        if ($group === '') return false;
        $this->resolvers[$group] = $resolver;
        return true;
    }

    public function has(string $type): bool
    {
        $this->boot();
        return isset($this->definitions[$type]);
    }

    public function definitions(): array
    {
        $this->boot();
        return array_values(array_map(static function (array $definition): array {
            unset($definition['_packageDir'], $definition['migrations']);
            return $definition;
        }, $this->definitions));
    }

    public function categories(): array
    {
        $this->boot();
        return array_values($this->categories);
    }

    private function default_categories(): array
    {
        return [
            ['id' => 'general_widgets', 'label' => 'ویجت‌های عمومی'],
            ['id' => 'media_widgets', 'label' => 'رسانه و تعامل'],
            ['id' => 'business_widgets', 'label' => 'شخصی و شرکتی'],
            ['id' => 'wordpress_widgets', 'label' => 'وردپرس'],
            ['id' => 'form_widgets', 'label' => 'فرم و جذب مخاطب'],
            ['id' => 'woo_archive', 'label' => 'فروشگاه و آرشیو'],
            ['id' => 'woo_product', 'label' => 'محصول تکی'],
            ['id' => 'woo_account', 'label' => 'خرید و حساب کاربری'],
        ];
    }

    private function discover_packages(): void
    {
        $root = defined('WOACT_CORE_DIR') ? WOACT_CORE_DIR . 'widgets/' : dirname(__DIR__) . '/widgets/';
        foreach ((array) glob($root . '*/widget.php') as $file) {
            $definition = include $file;
            if (!is_array($definition)) continue;
            $definition['_packageDir'] = dirname($file);
            $definition['package'] = basename(dirname($file));
            if ($this->register($definition)) $this->packages[$definition['type']] = dirname($file);
        }
    }

    public function migrate_props(string $type, array $props, string $from_version = '0.0.0'): array
    {
        $this->boot();
        $definition = $this->definitions[sanitize_key($type)] ?? [];
        foreach ((array) ($definition['migrations'] ?? []) as $version => $migration) {
            if (is_callable($migration) && version_compare($from_version, (string) $version, '<')) $props = (array) $migration($props);
        }
        return $props;
    }

    public function enqueue_assets(array $document, bool $editor = false): void
    {
        $this->boot();
        $types = $editor ? array_keys($this->packages) : $this->collect_types($document);
        foreach ($types as $type) {
            $definition = $this->definitions[$type] ?? [];
            $dir = (string) ($definition['_packageDir'] ?? '');
            if ($dir === '' || !defined('WOACT_CORE_FILE')) continue;
            $base = 'widgets/' . basename($dir) . '/';
            $handle = 'woact-widget-' . sanitize_key($type);
            $style = $dir . '/style.css';
            $script = $dir . '/runtime.js';
            if (is_file($style)) wp_enqueue_style($handle, plugins_url($base . 'style.css', WOACT_CORE_FILE), ['woact-page-runtime'], (string) filemtime($style));
            if (is_file($script)) wp_enqueue_script($handle, plugins_url($base . 'runtime.js', WOACT_CORE_FILE), ['woact-widget-runtime-registry'], (string) filemtime($script), true);
        }
    }

    public function collect_types(array $document): array
    {
        $types = [];
        $walk = static function (array $nodes) use (&$walk, &$types): void {
            foreach ($nodes as $node) {
                if (!is_array($node)) continue;
                $types[(string) ($node['type'] ?? '')] = true;
                $walk((array) ($node['children'] ?? []));
            }
        };
        $walk((array) ($document['nodes'] ?? []));
        return array_keys($types);
    }

    public function required_assets(array $document): array
    {
        $this->boot();
        $assets = [];
        foreach ($this->collect_types($document) as $type) {
            foreach ((array) ($this->definitions[$type]['assets'] ?? []) as $asset) $assets[$asset] = true;
        }
        return array_keys($assets);
    }

    public function runtime_data(array $document, int $post_id = 0): array
    {
        $this->boot();
        $groups = [];
        $used = $this->collect_types($document);
        foreach ($used as $type) {
            $group = (string) ($this->definitions[$type]['dataGroup'] ?? '');
            if ($group !== '') $groups[$group] = true;
        }
        $data = ['usedTypes' => $used, 'assets' => $this->required_assets($document)];
        if (isset($groups['posts'])) $data['posts'] = $this->resolve_posts();
        if (isset($groups['taxonomies'])) $data['taxonomies'] = $this->resolve_taxonomies();
        if (isset($groups['wordpress'])) $data['wordpress'] = $this->resolve_wordpress($post_id, $document);
        if (isset($groups['commerce'])) $data['commerce'] = $this->resolve_commerce();
        if (isset($groups['product'])) $data['product'] = $this->resolve_product($post_id);
        if (isset($groups['account'])) $data['account'] = $this->resolve_account();
        foreach (array_keys($groups) as $group) {
            if (isset($this->resolvers[$group])) $data[$group] = (array) call_user_func($this->resolvers[$group], $document, $post_id, $this);
        }
        return function_exists('apply_filters') ? apply_filters('woact_widget_runtime_data', $data, $document, $post_id) : $data;
    }

    private function default_definitions(): array
    {
        $f = static fn(string $id, string $label, string $type = 'text', $default = '', array $options = []): array => compact('id', 'label', 'type', 'default', 'options');
        $simple = static fn(string $type, string $label, string $icon, string $category, array $defaults = [], array $fields = [], string $group = '', array $assets = []): array => ['type' => $type, 'label' => $label, 'icon' => $icon, 'category' => $category, 'defaults' => $defaults, 'fields' => $fields, 'dataGroup' => $group, 'assets' => $assets];
        $defs = [
            $simple('badge', 'نشان', 'tag', 'general_widgets', ['text' => 'جدید'], [$f('text', 'متن')]),
            $simple('image_box', 'جعبه تصویر', 'format-image', 'general_widgets', ['title' => 'عنوان', 'text' => 'توضیحات', 'url' => ''], [$f('title', 'عنوان'), $f('text', 'توضیحات', 'textarea'), $f('url', 'تصویر', 'url')]),
            $simple('icon_box', 'جعبه آیکون', 'star-filled', 'general_widgets', ['icon' => '★', 'title' => 'عنوان', 'text' => 'توضیحات'], [$f('icon', 'آیکون'), $f('title', 'عنوان'), $f('text', 'توضیحات', 'textarea')]),
            $simple('gallery', 'گالری', 'format-gallery', 'media_widgets', ['images' => '', 'columns' => 3], [$f('images', 'هر تصویر در یک خط', 'textarea'), $f('columns', 'تعداد ستون', 'number', 3)], '', ['gallery']),
            $simple('video', 'ویدئو', 'video-alt3', 'media_widgets', ['url' => '', 'poster' => '', 'controls' => true], [$f('url', 'نشانی ویدئو', 'url'), $f('poster', 'پوستر', 'url')], '', ['media']),
            $simple('audio', 'صوت', 'format-audio', 'media_widgets', ['url' => ''], [$f('url', 'نشانی صوت', 'url')], '', ['media']),
            $simple('carousel', 'اسلایدر', 'slides', 'media_widgets', ['items' => "اسلاید اول|\nاسلاید دوم|", 'autoplay' => true], [$f('items', 'عنوان|تصویر؛ هر خط یک اسلاید', 'textarea')], '', ['carousel']),
            $simple('tabs', 'تب‌ها', 'index-card', 'media_widgets', ['items' => "تب اول|محتوای اول\nتب دوم|محتوای دوم"], [$f('items', 'عنوان|محتوا', 'textarea')], '', ['interactive']),
            $simple('accordion', 'آکاردئون', 'menu-alt3', 'media_widgets', ['items' => "پرسش اول|پاسخ اول\nپرسش دوم|پاسخ دوم"], [$f('items', 'عنوان|محتوا', 'textarea')], '', ['interactive']),
            $simple('countdown', 'شمارش معکوس', 'clock', 'media_widgets', ['date' => '', 'label' => 'تا شروع'], [$f('date', 'زمان ISO'), $f('label', 'عنوان')], '', ['timer']),
            $simple('counter', 'شمارنده', 'chart-bar', 'media_widgets', ['value' => 100, 'suffix' => '+', 'label' => 'مشتری'], [$f('value', 'عدد', 'number', 100), $f('suffix', 'پسوند'), $f('label', 'عنوان')], '', ['counter']),
            $simple('progress', 'نوار پیشرفت', 'performance', 'media_widgets', ['value' => 75, 'label' => 'پیشرفت'], [$f('value', 'درصد', 'number', 75), $f('label', 'عنوان')]),
            $simple('hero', 'هیرو', 'cover-image', 'business_widgets', ['eyebrow' => 'WOACT', 'title' => 'یک عنوان قدرتمند', 'text' => 'توضیح کوتاه ارزش پیشنهادی', 'buttonLabel' => 'شروع کنید', 'buttonUrl' => '#', 'image' => ''], [$f('eyebrow', 'روتیتر'), $f('title', 'عنوان'), $f('text', 'توضیحات', 'textarea'), $f('buttonLabel', 'متن دکمه'), $f('buttonUrl', 'پیوند دکمه', 'url'), $f('image', 'تصویر', 'url')]),
            $simple('services', 'خدمات', 'screenoptions', 'business_widgets', ['items' => "طراحی|شرح خدمت\nتوسعه|شرح خدمت\nپشتیبانی|شرح خدمت"], [$f('items', 'عنوان|توضیح', 'textarea')]),
            $simple('team', 'تیم', 'groups', 'business_widgets', ['items' => "نام عضو|سمت|\nنام عضو|سمت|"], [$f('items', 'نام|سمت|تصویر', 'textarea')]),
            $simple('testimonial', 'نظر مشتری', 'format-chat', 'business_widgets', ['text' => 'تجربه بسیار خوبی بود.', 'name' => 'نام مشتری', 'role' => 'سمت'], [$f('text', 'متن', 'textarea'), $f('name', 'نام'), $f('role', 'سمت')]),
            $simple('client_logos', 'لوگوی مشتریان', 'images-alt2', 'business_widgets', ['items' => "مشتری اول|\nمشتری دوم|"], [$f('items', 'نام|تصویر', 'textarea')]),
            $simple('pricing', 'جدول قیمت', 'money-alt', 'business_widgets', ['title' => 'حرفه‌ای', 'price' => '۹۹۰٬۰۰۰', 'features' => "ویژگی اول\nویژگی دوم", 'buttonLabel' => 'انتخاب'], [$f('title', 'عنوان'), $f('price', 'قیمت'), $f('features', 'ویژگی‌ها', 'textarea'), $f('buttonLabel', 'متن دکمه')]),
            $simple('faq', 'پرسش‌های متداول', 'editor-help', 'business_widgets', ['items' => "پرسش اول|پاسخ اول\nپرسش دوم|پاسخ دوم"], [$f('items', 'پرسش|پاسخ', 'textarea')], '', ['interactive']),
            $simple('timeline', 'خط زمانی', 'backup', 'business_widgets', ['items' => "۱۴۰۵|مرحله اول\n۱۴۰۶|مرحله دوم"], [$f('items', 'زمان|شرح', 'textarea')]),
            $simple('portfolio', 'نمونه‌کارها', 'portfolio', 'business_widgets', ['items' => "پروژه اول|دسته|\nپروژه دوم|دسته|"], [$f('items', 'عنوان|دسته|تصویر', 'textarea')]),
            $simple('contact_cta', 'دعوت به تماس', 'phone', 'business_widgets', ['title' => 'برای شروع آماده‌اید؟', 'text' => 'با ما تماس بگیرید.', 'buttonLabel' => 'تماس', 'buttonUrl' => '#'], [$f('title', 'عنوان'), $f('text', 'توضیح'), $f('buttonLabel', 'دکمه'), $f('buttonUrl', 'پیوند', 'url')]),
            $simple('map', 'نقشه', 'location-alt', 'business_widgets', ['embedUrl' => '', 'title' => 'موقعیت ما'], [$f('embedUrl', 'نشانی Embed', 'url'), $f('title', 'عنوان')]),
            $simple('social_profiles', 'شبکه‌های اجتماعی', 'share', 'business_widgets', ['items' => "Instagram|https://instagram.com/\nTelegram|https://t.me/"], [$f('items', 'عنوان|پیوند', 'textarea')]),
        ];
        foreach ([
            ['post_grid', 'شبکه نوشته‌ها', 'grid-view', 'posts'], ['post_list', 'فهرست نوشته‌ها', 'list-view', 'posts'], ['single_post', 'نوشته تکی', 'media-document', 'wordpress'],
            ['post_title', 'عنوان نوشته', 'heading', 'wordpress'], ['post_content', 'محتوای نوشته', 'text-page', 'wordpress'], ['post_image', 'تصویر شاخص', 'format-image', 'wordpress'],
            ['author', 'نویسنده', 'admin-users', 'wordpress'], ['comments', 'دیدگاه‌ها', 'admin-comments', 'wordpress'], ['categories', 'دسته‌ها', 'category', 'taxonomies'], ['tags', 'برچسب‌ها', 'tag', 'taxonomies'],
            ['search_widget', 'جست‌وجو', 'search', 'wordpress'], ['breadcrumb_widget', 'مسیر راهنما', 'arrow-left-alt2', 'wordpress'], ['menu_widget', 'منو', 'menu', 'wordpress'],
            ['login_widget', 'ورود', 'lock', 'account'], ['user_profile', 'پروفایل کاربر', 'admin-users', 'account'], ['shortcode', 'کد کوتاه', 'shortcode', 'wordpress'],
        ] as $item) {
            $fields = [];
            $defaults = [];
            if (in_array($item[0], ['post_grid', 'post_list'], true)) { $defaults = ['count' => 6, 'columns' => 3, 'postType' => 'post']; $fields = [$f('count', 'تعداد', 'number', 6), $f('columns', 'ستون', 'number', 3), $f('postType', 'نوع محتوا', 'text', 'post')]; }
            if ($item[0] === 'shortcode') { $defaults = ['code' => '']; $fields = [$f('code', 'Shortcode')]; }
            if ($item[0] === 'menu_widget') { $defaults = ['location' => 'primary']; $fields = [$f('location', 'جایگاه منو')]; }
            $defs[] = $simple($item[0], $item[1], $item[2], 'wordpress_widgets', $defaults, $fields, $item[3]);
        }
        $defs[] = $simple('lead_form', 'فرم تماس', 'feedback', 'form_widgets', ['title' => 'تماس با ما', 'fields' => "name|نام|text|required\nemail|ایمیل|email|required\nmessage|پیام|textarea|required", 'buttonLabel' => 'ارسال'], [$f('title', 'عنوان'), $f('fields', 'کلید|عنوان|نوع|required', 'textarea'), $f('buttonLabel', 'متن دکمه')], '', ['forms']);
        foreach ([
            ['product_grid', 'شبکه محصولات', 'grid-view', 'commerce'], ['product_categories', 'دسته‌های محصول', 'category', 'commerce'], ['product_filters', 'فیلتر محصولات', 'filter', 'commerce'], ['product_sort', 'مرتب‌سازی', 'sort', 'commerce'], ['product_results_count', 'تعداد نتایج', 'editor-ol', 'commerce'], ['product_pagination', 'صفحه‌بندی', 'arrow-left-alt', 'commerce'], ['sale_products', 'محصولات تخفیف‌دار', 'tickets-alt', 'commerce'], ['featured_products', 'محصولات ویژه', 'star-filled', 'commerce'], ['recent_products', 'محصولات جدید', 'clock', 'commerce'],
        ] as $item) $defs[] = $simple($item[0], $item[1], $item[2], 'woo_archive', ['count' => 8, 'columns' => 4], [$f('count', 'تعداد', 'number', 8), $f('columns', 'ستون', 'number', 4)], $item[3], ['commerce']);
        foreach ([
            ['product_title', 'عنوان محصول'], ['product_gallery', 'گالری محصول'], ['product_price', 'قیمت محصول'], ['product_rating', 'امتیاز محصول'], ['product_stock', 'موجودی محصول'], ['product_variations', 'متغیرهای محصول'], ['add_to_cart', 'افزودن به سبد'], ['buy_now', 'خرید سریع'], ['product_meta', 'مشخصات محصول'], ['product_tabs', 'تب‌های محصول'], ['product_reviews', 'نظرات محصول'], ['related_products', 'محصولات مرتبط'], ['upsell_products', 'پیشنهادهای بیشتر'], ['delivery_estimate', 'زمان تحویل'],
        ] as $item) $defs[] = $simple($item[0], $item[1], 'cart', 'woo_product', [], [], 'product', ['commerce']);
        foreach ([
            ['mini_cart', 'سبد خرید کوچک'], ['cart_page', 'صفحه سبد خرید'], ['coupon', 'کد تخفیف'], ['checkout', 'تسویه حساب'], ['order_review', 'مرور سفارش'], ['thank_you', 'تشکر خرید'], ['account_nav', 'منوی حساب'], ['orders', 'سفارش‌ها'], ['downloads', 'دانلودها'], ['addresses', 'نشانی‌ها'], ['wishlist_widget', 'علاقه‌مندی‌ها'], ['compare_widget', 'مقایسه'], ['waitlist', 'لیست انتظار'],
        ] as $item) $defs[] = $simple($item[0], $item[1], 'cart', 'woo_account', [], [], 'account', ['commerce']);
        return $defs;
    }

    private function resolve_posts(): array
    {
        $posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 12]);
        return array_map(static fn(WP_Post $post): array => ['id' => $post->ID, 'title' => get_the_title($post), 'url' => get_permalink($post), 'excerpt' => wp_trim_words(wp_strip_all_tags($post->post_content), 22), 'image' => get_the_post_thumbnail_url($post, 'medium_large') ?: ''], $posts);
    }

    private function resolve_taxonomies(): array
    {
        $map = static fn($term): array => ['id' => $term->term_id, 'name' => $term->name, 'url' => get_term_link($term)];
        $categories = get_terms(['taxonomy' => 'category', 'hide_empty' => true, 'number' => 30]);
        $tags = get_terms(['taxonomy' => 'post_tag', 'hide_empty' => true, 'number' => 30]);
        return ['categories' => is_wp_error($categories) ? [] : array_map($map, $categories), 'tags' => is_wp_error($tags) ? [] : array_map($map, $tags)];
    }

    private function resolve_wordpress(int $post_id, array $document): array
    {
        $data = ['shortcodes' => [], 'comments' => [], 'menus' => []];
        if ($post_id) {
            $data['post'] = ['title' => get_the_title($post_id), 'content' => apply_filters('the_content', (string) get_post_field('post_content', $post_id)), 'image' => get_the_post_thumbnail_url($post_id, 'large') ?: '', 'author' => get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id))];
            $comments = get_comments(['post_id' => $post_id, 'status' => 'approve', 'number' => 20]);
            $data['comments'] = array_map(static fn($comment): array => ['id' => (int) $comment->comment_ID, 'author' => $comment->comment_author, 'content' => wp_strip_all_tags($comment->comment_content)], $comments);
        }
        $this->walk_nodes((array) ($document['nodes'] ?? []), function (array $node) use (&$data): void {
            if (($node['type'] ?? '') === 'shortcode' && !empty($node['props']['code'])) $data['shortcodes'][$node['id']] = do_shortcode((string) $node['props']['code']);
        });
        return $data;
    }

    private function resolve_commerce(): array
    {
        if (!function_exists('wc_get_products')) return ['available' => false, 'products' => [], 'categories' => [], 'cartCount' => 0];
        $products = wc_get_products(['status' => 'publish', 'limit' => 16, 'orderby' => 'date', 'order' => 'DESC']);
        $items = array_map(static function ($product): array { return ['id' => $product->get_id(), 'title' => $product->get_name(), 'url' => $product->get_permalink(), 'priceHtml' => $product->get_price_html(), 'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail') ?: '', 'rating' => (float) $product->get_average_rating(), 'stock' => $product->get_stock_status(), 'addToCartUrl' => $product->add_to_cart_url()]; }, $products);
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 30]);
        return ['available' => true, 'products' => $items, 'categories' => is_wp_error($terms) ? [] : array_map(static fn($term): array => ['id' => $term->term_id, 'name' => $term->name, 'url' => get_term_link($term)], $terms), 'cartCount' => function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0, 'cartUrl' => wc_get_cart_url(), 'checkoutUrl' => wc_get_checkout_url()];
    }

    private function resolve_product(int $post_id): array
    {
        if (!function_exists('wc_get_product')) return ['available' => false];
        $product = wc_get_product($post_id ?: get_the_ID());
        if (!$product) return ['available' => false];
        return ['available' => true, 'id' => $product->get_id(), 'title' => $product->get_name(), 'priceHtml' => $product->get_price_html(), 'description' => $product->get_description(), 'shortDescription' => $product->get_short_description(), 'image' => wp_get_attachment_image_url($product->get_image_id(), 'large') ?: '', 'gallery' => array_values(array_filter(array_map(static fn($id) => wp_get_attachment_image_url($id, 'large'), $product->get_gallery_image_ids()))), 'rating' => (float) $product->get_average_rating(), 'stock' => $product->get_stock_status(), 'sku' => $product->get_sku(), 'addToCartUrl' => $product->add_to_cart_url()];
    }

    private function resolve_account(): array
    {
        $user = wp_get_current_user();
        return ['loggedIn' => is_user_logged_in(), 'name' => is_user_logged_in() ? $user->display_name : '', 'loginUrl' => wp_login_url(), 'logoutUrl' => is_user_logged_in() ? wp_logout_url(home_url('/')) : '', 'accountUrl' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(), 'ordersUrl' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : '#', 'downloadsUrl' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('downloads') : '#', 'addressesUrl' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('edit-address') : '#'];
    }

    private function walk_nodes(array $nodes, callable $callback): void
    {
        foreach ($nodes as $node) { if (!is_array($node)) continue; $callback($node); $this->walk_nodes((array) ($node['children'] ?? []), $callback); }
    }
}
