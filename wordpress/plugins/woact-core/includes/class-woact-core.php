<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class Woact_Core
{
    private const META_RENDER_MODE = '_woact_render_mode';
    private static ?self $instance = null;
    private bool $booted = false;
    private Woact_Settings $settings;
    private Woact_Settings_REST_Controller $settings_controller;

    private function __construct()
    {
        $schema = require WOACT_CORE_DIR . 'includes/settings-schema.php';
        $this->settings = new Woact_Settings(is_array($schema) ? $schema : []);
        $this->settings_controller = new Woact_Settings_REST_Controller($this->settings);
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate(): void
    {
        self::instance()->settings->install_or_upgrade();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        if ((int) get_option(Woact_Settings::DB_VERSION_OPTION, 0) < Woact_Settings::SCHEMA_VERSION) {
            $this->settings->install_or_upgrade();
        }

        load_plugin_textdomain('woact-core', false, dirname(plugin_basename(WOACT_CORE_FILE)) . '/languages');
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('add_meta_boxes', [$this, 'register_render_meta_box']);
        add_action('save_post', [$this, 'save_render_meta_box']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'apply_frontend_settings'], 20);
        add_action('wp_body_open', [$this, 'render_classic_banner'], 5);
        add_action('wp_head', [$this, 'print_custom_head_script'], 99);
        add_action('wp_footer', [$this, 'print_custom_footer_script'], 99);
        add_action('template_redirect', [$this, 'maybe_render_maintenance'], 0);
        add_filter('plugin_action_links_' . plugin_basename(WOACT_CORE_FILE), [$this, 'add_settings_link']);
        $this->apply_performance_settings();
    }

    public function get_settings(): array
    {
        return $this->settings->get_values();
    }

    public function register_admin_page(): void
    {
        $settings = $this->get_settings();
        $menu_name = !empty($settings['white_label.enabled']) && !empty($settings['white_label.name'])
            ? (string) $settings['white_label.name']
            : __('Woact', 'woact-core');
        add_menu_page(
            $menu_name,
            $menu_name,
            'manage_options',
            'woact',
            [$this, 'render_settings_page'],
            'dashicons-layout',
            58
        );
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_woact') {
            return;
        }
        wp_enqueue_media();
        $css_file = WOACT_CORE_DIR . 'assets/admin-settings.css';
        $js_file = WOACT_CORE_DIR . 'assets/admin-settings.js';
        wp_enqueue_style(
            'woact-admin-settings',
            plugins_url('assets/admin-settings.css', WOACT_CORE_FILE),
            [],
            is_file($css_file) ? (string) filemtime($css_file) : WOACT_CORE_VERSION
        );
        wp_enqueue_script(
            'woact-admin-settings',
            plugins_url('assets/admin-settings.js', WOACT_CORE_FILE),
            ['wp-element', 'wp-api-fetch', 'wp-i18n'],
            is_file($js_file) ? (string) filemtime($js_file) : WOACT_CORE_VERSION,
            true
        );
        wp_add_inline_script('woact-admin-settings', 'window.WoactAdminConfig=' . wp_json_encode([
            'apiRoot' => esc_url_raw(rest_url('woact/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'version' => WOACT_CORE_VERSION,
            'locale' => get_locale(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';', 'before');
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap woact-admin-wrap" dir="rtl">
            <div id="woact-settings-root">
                <div class="woact-admin-loading">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('در حال بارگذاری تنظیمات Woact…', 'woact-core'); ?></p>
                </div>
            </div>
            <noscript><?php esc_html_e('برای استفاده از پنل تنظیمات، JavaScript مرورگر را فعال کنید.', 'woact-core'); ?></noscript>
        </div>
        <?php
    }

    public function register_render_meta_box(): void
    {
        $post_types = get_post_types(['public' => true, 'show_ui' => true], 'names');
        unset($post_types['attachment']);
        foreach ($post_types as $post_type) {
            add_meta_box(
                'woact-render-mode',
                __('نحوه نمایش Woact', 'woact-core'),
                [$this, 'render_meta_box'],
                $post_type,
                'side',
                'default'
            );
        }
    }

    public function render_meta_box($post): void
    {
        $value = get_post_meta($post->ID, self::META_RENDER_MODE, true) ?: 'inherit';
        wp_nonce_field('woact_render_mode_' . $post->ID, 'woact_render_mode_nonce');
        ?>
        <p><?php esc_html_e('این صفحه می‌تواند از تنظیم سراسری پیروی کند یا مستقل نمایش داده شود.', 'woact-core'); ?></p>
        <select name="woact_render_mode" style="width:100%">
            <option value="inherit" <?php selected($value, 'inherit'); ?>><?php esc_html_e('پیروی از تنظیم سراسری', 'woact-core'); ?></option>
            <option value="classic" <?php selected($value, 'classic'); ?>><?php esc_html_e('وردپرس / صفحه‌ساز', 'woact-core'); ?></option>
            <option value="react" <?php selected($value, 'react'); ?>><?php esc_html_e('React', 'woact-core'); ?></option>
        </select>
        <?php
    }

    public function save_render_meta_box(int $post_id): void
    {
        $nonce = isset($_POST['woact_render_mode_nonce']) ? sanitize_text_field(wp_unslash($_POST['woact_render_mode_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'woact_render_mode_' . $post_id)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $mode = isset($_POST['woact_render_mode']) ? sanitize_key(wp_unslash($_POST['woact_render_mode'])) : 'inherit';
        if (!in_array($mode, ['inherit', 'classic', 'react'], true)) {
            $mode = 'inherit';
        }
        if ($mode === 'inherit') {
            delete_post_meta($post_id, self::META_RENDER_MODE);
        } else {
            update_post_meta($post_id, self::META_RENDER_MODE, $mode);
        }
    }

    public function get_request_render_mode(): string
    {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_embed()) {
            return 'classic';
        }

        $post_id = is_singular() ? (int) get_queried_object_id() : 0;
        $post_type = $post_id ? (string) get_post_type($post_id) : $this->get_archive_post_type();
        $override = $post_id ? (string) get_post_meta($post_id, self::META_RENDER_MODE, true) : 'inherit';
        $settings = $this->get_settings();

        return Woact_Render_Policy::decide([
            'global_mode' => $settings['render.mode'],
            'override' => $override ?: 'inherit',
            'is_builder_document' => $post_id > 0 && $this->is_builder_document($post_id),
            'preserve_builders' => (bool) $settings['render.preserve_builders'],
            'post_type' => $post_type,
            'enabled_post_types' => $settings['render.post_types'],
        ]);
    }

    private function get_archive_post_type(): string
    {
        $queried = get_queried_object();
        if ($queried instanceof WP_Post_Type) {
            return $queried->name;
        }
        if (is_home() || is_category() || is_tag() || is_author() || is_date() || is_search()) {
            return 'post';
        }
        return '';
    }

    private function is_builder_document(int $post_id): bool
    {
        if (get_post_meta($post_id, '_elementor_edit_mode', true) === 'builder') {
            return true;
        }

        $builder_meta = [
            '_et_pb_use_builder',
            '_fl_builder_enabled',
            '_wpb_vc_js_status',
            '_bricks_editor_mode',
            '_breakdance_data',
            '_oxygen_vsb_data',
            'ct_builder_shortcodes',
        ];
        foreach ($builder_meta as $meta_key) {
            $value = get_post_meta($post_id, $meta_key, true);
            if ($value !== '' && $value !== false && $value !== null && $value !== 'off') {
                return true;
            }
        }

        $template = (string) get_page_template_slug($post_id);
        if (in_array($template, ['elementor_canvas', 'elementor_header_footer', 'elementor_theme', 'template-canvas.php'], true)) {
            return true;
        }

        $content = (string) get_post_field('post_content', $post_id);
        foreach (['[vc_row', '[et_pb_', '[fusion_builder_container'] as $builder_shortcode) {
            if (strpos($content, $builder_shortcode) !== false) {
                return true;
            }
        }

        return (bool) apply_filters('woact_is_builder_document', false, $post_id);
    }

    public function build_bootstrap_data(): array
    {
        $data = [
            'version' => WOACT_CORE_VERSION,
            'site' => [
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => home_url('/'),
                'locale' => get_locale(),
                'direction' => is_rtl() ? 'rtl' : 'ltr',
            ],
            'route' => [
                'kind' => $this->get_route_kind(),
                'canonical' => $this->get_canonical_url(),
            ],
            'navigation' => $this->get_navigation(),
            'content' => $this->get_route_content(),
            'settings' => $this->get_public_settings(),
        ];

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $data['viewer'] = [
                'id' => (int) $user->ID,
                'name' => $user->display_name,
                'restNonce' => wp_create_nonce('wp_rest'),
            ];
        }

        return apply_filters('woact_bootstrap_data', $data);
    }

    private function get_route_kind(): string
    {
        if (is_404()) {
            return 'not-found';
        }
        if (is_search()) {
            return 'search';
        }
        if (is_singular()) {
            return 'singular';
        }
        return 'archive';
    }

    private function get_canonical_url(): string
    {
        if (is_singular()) {
            return (string) get_permalink((int) get_queried_object_id());
        }
        global $wp;
        return home_url(isset($wp->request) ? trailingslashit($wp->request) : '/');
    }

    private function get_navigation(): array
    {
        $locations = get_nav_menu_locations();
        if (empty($locations['primary'])) {
            return [];
        }
        $items = wp_get_nav_menu_items((int) $locations['primary']);
        if (!is_array($items)) {
            return [];
        }
        return array_map(static function ($item): array {
            return [
                'id' => (int) $item->ID,
                'parentId' => (int) $item->menu_item_parent,
                'label' => $item->title,
                'url' => $item->url,
                'target' => $item->target ?: '_self',
            ];
        }, $items);
    }

    private function get_route_content(): array
    {
        if (is_404()) {
            return ['title' => __('صفحه پیدا نشد', 'woact-core'), 'items' => []];
        }

        if (is_singular()) {
            $post_id = (int) get_queried_object_id();
            return $this->serialize_post($post_id, true);
        }

        global $wp_query;
        $items = [];
        foreach ((array) $wp_query->posts as $post) {
            if ($post instanceof WP_Post) {
                $items[] = $this->serialize_post((int) $post->ID, false);
            }
        }
        return [
            'title' => wp_strip_all_tags(get_the_archive_title() ?: (is_search() ? sprintf(__('نتایج جست‌وجو برای «%s»', 'woact-core'), get_search_query()) : get_bloginfo('name'))),
            'items' => $items,
            'pagination' => [
                'page' => max(1, (int) get_query_var('paged')),
                'pages' => max(1, (int) $wp_query->max_num_pages),
            ],
        ];
    }

    private function serialize_post(int $post_id, bool $with_content): array
    {
        $raw_excerpt = (string) get_post_field('post_excerpt', $post_id);
        if ($raw_excerpt === '') {
            $raw_excerpt = wp_trim_words(wp_strip_all_tags(strip_shortcodes((string) get_post_field('post_content', $post_id))), 34);
        }
        $item = [
            'id' => $post_id,
            'type' => (string) get_post_type($post_id),
            'title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'excerpt' => $raw_excerpt,
            'date' => get_the_date(DATE_W3C, $post_id),
            'featuredImage' => get_the_post_thumbnail_url($post_id, 'large') ?: null,
        ];
        if ($with_content) {
            $item['html'] = apply_filters('the_content', (string) get_post_field('post_content', $post_id));
        }
        return $item;
    }

    public function register_rest_routes(): void
    {
        $this->settings_controller->register_routes();
        register_rest_route('woact/v1', '/status', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback' => function (): WP_REST_Response {
                $settings = $this->get_settings();
                return new WP_REST_Response([
                    'version' => WOACT_CORE_VERSION,
                    'schemaVersion' => Woact_Settings::SCHEMA_VERSION,
                    'mode' => $settings['render.mode'],
                    'builderCompatibility' => (bool) $settings['render.preserve_builders'],
                ]);
            },
        ]);
    }

    private function get_public_settings(): array
    {
        $settings = $this->get_settings();
        $allowed = [
            'layout.container_width',
            'layout.content_width',
            'layout.card_radius',
            'layout.direction',
            'style.primary',
            'style.accent',
            'style.text',
            'style.muted',
            'style.border',
            'style.link',
            'style.gradient',
            'style.button_radius',
            'style.input_radius',
            'banner.enabled',
            'banner.text',
            'banner.link',
            'banner.background',
            'banner.text_color',
            'banner.closable',
            'footer.enabled',
            'footer.copyright',
            'footer.background',
            'footer.text_color',
        ];
        return array_intersect_key($settings, array_flip($allowed));
    }

    public function apply_frontend_settings(): void
    {
        $settings = $this->get_settings();
        $body = is_array($settings['typography.body'] ?? null) ? $settings['typography.body'] : [];
        $heading = is_array($settings['typography.headings'] ?? null) ? $settings['typography.headings'] : [];
        $custom_font_url = (string) ($settings['typography.custom_font_url'] ?? '');
        $custom_font_name = (string) ($settings['typography.custom_font_name'] ?? '');
        $font_face = '';
        if ($custom_font_url !== '' && $custom_font_name !== '') {
            $font_face = sprintf(
                '@font-face{font-family:"%s";src:url("%s");font-display:%s;}',
                esc_attr($custom_font_name),
                esc_url_raw($custom_font_url),
                esc_attr((string) ($settings['typography.font_display'] ?? 'swap'))
            );
        }
        $css = $font_face . sprintf(
            ':root{--woact-primary:%1$s;--woact-accent:%2$s;--woact-text:%3$s;--woact-muted:%4$s;--woact-border:%5$s;--woact-background:%6$s;--woact-link:%7$s;--woact-radius:%8$dpx;--woact-container:%9$dpx;--woact-content-width:%10$dpx;--woact-button-radius:%11$dpx;--woact-input-radius:%12$dpx;}body{font-family:"%13$s",Tahoma,Arial,sans-serif;font-size:%14$dpx;line-height:%15$s;}h1,h2,h3,h4,h5,h6{font-family:"%16$s",Tahoma,Arial,sans-serif;font-weight:%17$s;line-height:%18$s;}a{color:var(--woact-link);}',
            (string) $settings['style.primary'],
            (string) $settings['style.accent'],
            (string) $settings['style.text'],
            (string) $settings['style.muted'],
            (string) $settings['style.border'],
            (string) $settings['layout.site_background'],
            (string) $settings['style.link'],
            (int) $settings['layout.card_radius'],
            (int) $settings['layout.container_width'],
            (int) $settings['layout.content_width'],
            (int) $settings['style.button_radius'],
            (int) $settings['style.input_radius'],
            esc_attr((string) ($body['family'] ?? 'Tahoma')),
            (int) ($body['size'] ?? 16),
            esc_attr((string) ($body['lineHeight'] ?? '1.8')),
            esc_attr((string) ($heading['family'] ?? 'Tahoma')),
            esc_attr((string) ($heading['weight'] ?? '700')),
            esc_attr((string) ($heading['lineHeight'] ?? '1.4'))
        );
        if (in_array($settings['layout.direction'], ['rtl', 'ltr'], true)) {
            $css .= 'body{direction:' . $settings['layout.direction'] . ';}';
        }
        if (!empty($settings['style.button_uppercase'])) {
            $css .= '.button,button,.wp-element-button{text-transform:uppercase;}';
        }
        if (!empty($settings['style.focus_ring'])) {
            $css .= ':focus-visible{outline:3px solid color-mix(in srgb,var(--woact-primary),transparent 35%);outline-offset:3px;}';
        }
        $css .= sprintf(
            '.woact-site-footer,.woact-react-footer{background:%1$s;color:%2$s}.woact-site-footer a,.woact-react-footer a{color:inherit}.woact-entry,.woact-card,.woact-empty{border-radius:%3$dpx}.woact-field input,.woact-field select,.woact-field textarea,input:not([type="checkbox"]):not([type="radio"]),select,textarea{border-radius:%4$dpx}.button,button,.wp-element-button{border-radius:%5$dpx}',
            (string) $settings['footer.background'],
            (string) $settings['footer.text_color'],
            (int) $settings['layout.card_radius'],
            (int) $settings['style.input_radius'],
            (int) $settings['style.button_radius']
        );
        if (!empty($settings['custom.enabled']) && !empty($settings['custom.css'])) {
            $css .= "\n" . (string) $settings['custom.css'];
        }
        wp_add_inline_style('woact-style', $css);
    }

    public function render_classic_banner(): void
    {
        $settings = $this->get_settings();
        if (
            empty($settings['banner.enabled'])
            || (function_exists('woact_core_get_request_render_mode') && woact_core_get_request_render_mode() === 'react')
        ) {
            return;
        }
        $text = (string) $settings['banner.text'];
        if ($text === '') {
            return;
        }
        $content = esc_html($text);
        if (!empty($settings['banner.link'])) {
            $content = '<a href="' . esc_url((string) $settings['banner.link']) . '">' . $content . '</a>';
        }
        $style = sprintf(
            'background:%s;color:%s',
            esc_attr((string) $settings['banner.background']),
            esc_attr((string) $settings['banner.text_color'])
        );
        $close = !empty($settings['banner.closable'])
            ? '<button type="button" data-woact-dismiss-banner aria-label="' . esc_attr__('بستن اعلان', 'woact-core') . '">×</button>'
            : '';
        echo '<aside class="woact-announcement" role="status" style="' . $style . '">' . wp_kses_post($content) . $close . '</aside>';
    }

    public function print_custom_head_script(): void
    {
        $settings = $this->get_settings();
        if (!empty($settings['custom.enabled']) && !empty($settings['custom.js_head'])) {
            wp_print_inline_script_tag((string) $settings['custom.js_head'], ['id' => 'woact-custom-head']);
        }
    }

    public function print_custom_footer_script(): void
    {
        $settings = $this->get_settings();
        if (!empty($settings['banner.enabled']) && !empty($settings['banner.closable'])) {
            wp_print_inline_script_tag(
                'document.addEventListener("click",function(event){var button=event.target.closest("[data-woact-dismiss-banner]");if(button){var banner=button.closest(".woact-announcement");if(banner){banner.remove();}}});',
                ['id' => 'woact-banner-controls']
            );
        }
        if (!empty($settings['custom.enabled']) && !empty($settings['custom.js_footer'])) {
            wp_print_inline_script_tag((string) $settings['custom.js_footer'], ['id' => 'woact-custom-footer']);
        }
    }

    public function maybe_render_maintenance(): void
    {
        $settings = $this->get_settings();
        if (
            empty($settings['maintenance.enabled'])
            || is_admin()
            || wp_doing_ajax()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || current_user_can('manage_options')
        ) {
            return;
        }
        status_header(503);
        nocache_headers();
        $title = esc_html((string) $settings['maintenance.title']);
        $message = nl2br(esc_html((string) $settings['maintenance.message']));
        $image = !empty($settings['maintenance.image'])
            ? '<img src="' . esc_url((string) $settings['maintenance.image']) . '" alt="" style="max-width:360px;height:auto">'
            : '';
        wp_die(
            '<main style="max-width:720px;margin:12vh auto;text-align:center;font-family:Tahoma,Arial,sans-serif">' . $image . '<h1>' . $title . '</h1><p>' . $message . '</p></main>',
            $title,
            ['response' => 503]
        );
    }

    private function apply_performance_settings(): void
    {
        $settings = $this->get_settings();
        if (!empty($settings['performance.disable_emojis'])) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
        }
        if (!empty($settings['performance.disable_embeds'])) {
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
        }
    }

    public function add_settings_link(array $links): array
    {
        array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=woact')) . '">' . esc_html__('تنظیمات', 'woact-core') . '</a>');
        return $links;
    }
}

