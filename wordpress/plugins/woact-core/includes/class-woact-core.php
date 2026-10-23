<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class Woact_Core
{
    private const OPTION = 'woact_stage1_settings';
    private const META_RENDER_MODE = '_woact_render_mode';
    private static ?self $instance = null;
    private bool $booted = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate(): void
    {
        if (get_option(self::OPTION, null) === null) {
            add_option(self::OPTION, self::defaults());
        }
    }

    public static function defaults(): array
    {
        return [
            'render_mode' => 'hybrid',
            'preserve_builders' => 1,
            'react_post_types' => ['page', 'post'],
        ];
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        load_plugin_textdomain('woact-core', false, dirname(plugin_basename(WOACT_CORE_FILE)) . '/languages');
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes', [$this, 'register_render_meta_box']);
        add_action('save_post', [$this, 'save_render_meta_box']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_filter('plugin_action_links_' . plugin_basename(WOACT_CORE_FILE), [$this, 'add_settings_link']);
    }

    public function get_settings(): array
    {
        $saved = get_option(self::OPTION, []);
        return array_merge(self::defaults(), is_array($saved) ? $saved : []);
    }

    public function register_admin_page(): void
    {
        add_menu_page(
            __('Woact', 'woact-core'),
            __('Woact', 'woact-core'),
            'manage_options',
            'woact',
            [$this, 'render_settings_page'],
            'dashicons-layout',
            58
        );
    }

    public function register_settings(): void
    {
        register_setting('woact_stage1', self::OPTION, [
            'type' => 'object',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => self::defaults(),
        ]);
    }

    public function sanitize_settings($input): array
    {
        $input = is_array($input) ? $input : [];
        $allowed_modes = ['classic', 'hybrid', 'react'];
        $mode = sanitize_key((string) ($input['render_mode'] ?? 'hybrid'));
        if (!in_array($mode, $allowed_modes, true)) {
            $mode = 'hybrid';
        }

        $public_types = get_post_types(['public' => true], 'names');
        unset($public_types['attachment']);
        $selected = array_map('sanitize_key', (array) ($input['react_post_types'] ?? []));
        $selected = array_values(array_intersect($selected, array_values($public_types)));

        return [
            'render_mode' => $mode,
            'preserve_builders' => empty($input['preserve_builders']) ? 0 : 1,
            'react_post_types' => $selected,
        ];
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $settings = $this->get_settings();
        $post_types = get_post_types(['public' => true], 'objects');
        unset($post_types['attachment']);
        ?>
        <div class="wrap woact-settings" dir="rtl">
            <h1><?php esc_html_e('تنظیمات پایه Woact', 'woact-core'); ?></h1>
            <p><?php esc_html_e('در استیج اول فقط نحوهٔ رندر سایت را کنترل می‌کنیم تا انتقال به React تدریجی و امن باشد.', 'woact-core'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('woact_stage1'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('حالت رندر سایت', 'woact-core'); ?></th>
                        <td>
                            <?php
                            $modes = [
                                'classic' => ['وردپرس و صفحه‌سازها', 'همهٔ صفحات با رندر معمول وردپرس نمایش داده می‌شوند.'],
                                'hybrid' => ['ترکیبی — پیشنهاد فعلی', 'صفحات فعلی دست‌نخورده می‌مانند و React را برای هر صفحه جداگانه فعال می‌کنید.'],
                                'react' => ['React سراسری', 'انواع محتوای انتخاب‌شده با React نمایش داده می‌شوند؛ صفحات صفحه‌ساز همچنان محافظت می‌شوند.'],
                            ];
                            foreach ($modes as $value => [$label, $description]) :
                                ?>
                                <label style="display:block;margin:0 0 14px">
                                    <input type="radio" name="<?php echo esc_attr(self::OPTION); ?>[render_mode]" value="<?php echo esc_attr($value); ?>" <?php checked($settings['render_mode'], $value); ?>>
                                    <strong><?php echo esc_html($label); ?></strong><br>
                                    <span class="description"><?php echo esc_html($description); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('سازگاری با صفحه‌سازها', 'woact-core'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[preserve_builders]" value="1" <?php checked((int) $settings['preserve_builders'], 1); ?>>
                                <?php esc_html_e('صفحات ساخته‌شده با Elementor و صفحه‌سازهای شناخته‌شده با خروجی اصلی خودشان باز شوند', 'woact-core'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('انواع محتوای React', 'woact-core'); ?></th>
                        <td>
                            <?php foreach ($post_types as $post_type) : ?>
                                <label style="display:inline-block;margin:0 0 10px 18px">
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[react_post_types][]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $settings['react_post_types'], true)); ?>>
                                    <?php echo esc_html($post_type->labels->name); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('این انتخاب فقط در حالت React سراسری اعمال می‌شود.', 'woact-core'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('ذخیره تنظیمات', 'woact-core')); ?>
            </form>
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
            'global_mode' => $settings['render_mode'],
            'override' => $override ?: 'inherit',
            'is_builder_document' => $post_id > 0 && $this->is_builder_document($post_id),
            'preserve_builders' => (bool) $settings['preserve_builders'],
            'post_type' => $post_type,
            'enabled_post_types' => $settings['react_post_types'],
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
        register_rest_route('woact/v1', '/status', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback' => function (): WP_REST_Response {
                $settings = $this->get_settings();
                return new WP_REST_Response([
                    'version' => WOACT_CORE_VERSION,
                    'mode' => $settings['render_mode'],
                    'builderCompatibility' => (bool) $settings['preserve_builders'],
                ]);
            },
        ]);
    }

    public function add_settings_link(array $links): array
    {
        array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=woact')) . '">' . esc_html__('تنظیمات', 'woact-core') . '</a>');
        return $links;
    }
}

