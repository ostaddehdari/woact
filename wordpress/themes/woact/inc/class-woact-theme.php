<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class Woact_Theme
{
    private static ?self $instance = null;
    private bool $booted = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        add_action('after_setup_theme', [$this, 'setup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('template_include', [$this, 'select_template'], 100);
        add_filter('body_class', [$this, 'body_classes']);
        add_action('admin_notices', [$this, 'core_plugin_notice']);
    }

    public function setup(): void
    {
        load_theme_textdomain('woact', WOACT_THEME_DIR . '/languages');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('responsive-embeds');
        add_theme_support('align-wide');
        add_theme_support('custom-logo', ['height' => 80, 'width' => 260, 'flex-height' => true, 'flex-width' => true]);
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
        add_theme_support('woocommerce');
        add_theme_support('wc-product-gallery-zoom');
        add_theme_support('wc-product-gallery-lightbox');
        add_theme_support('wc-product-gallery-slider');
        register_nav_menus([
            'primary' => __('منوی اصلی', 'woact'),
            'footer' => __('منوی فوتر', 'woact'),
        ]);
    }

    public function enqueue_assets(): void
    {
        wp_enqueue_style('woact-style', get_stylesheet_uri(), [], WOACT_THEME_VERSION);

        if (!$this->is_react_request()) {
            return;
        }

        $react_css = WOACT_THEME_DIR . '/assets/css/react.css';
        $react_js = WOACT_THEME_DIR . '/assets/js/react-app.js';
        wp_enqueue_style(
            'woact-react',
            WOACT_THEME_URI . '/assets/css/react.css',
            ['woact-style'],
            is_file($react_css) ? (string) filemtime($react_css) : WOACT_THEME_VERSION
        );
        wp_enqueue_script(
            'woact-react-app',
            WOACT_THEME_URI . '/assets/js/react-app.js',
            array_values(array_filter(['wp-element', 'wp-api-fetch', 'wp-i18n', wp_script_is('woact-layout-runtime', 'registered') ? 'woact-layout-runtime' : null])),
            is_file($react_js) ? (string) filemtime($react_js) : WOACT_THEME_VERSION,
            true
        );

        $bootstrap = function_exists('woact_core_get_bootstrap_data')
            ? woact_core_get_bootstrap_data()
            : $this->fallback_bootstrap_data();
        wp_add_inline_script(
            'woact-react-app',
            'window.WoactBootstrap=' . wp_json_encode($bootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
            'before'
        );
    }

    public function select_template(string $template): string
    {
        if (!$this->is_react_request()) {
            return $template;
        }
        $react_template = WOACT_THEME_DIR . '/react-shell.php';
        return is_file($react_template) ? $react_template : $template;
    }

    private function is_react_request(): bool
    {
        if (!function_exists('woact_core_get_request_render_mode')) {
            return false;
        }
        return woact_core_get_request_render_mode() === 'react';
    }

    public function body_classes(array $classes): array
    {
        $classes[] = $this->is_react_request() ? 'woact-render-react' : 'woact-render-classic';
        return $classes;
    }

    public function core_plugin_notice(): void
    {
        if (!current_user_can('activate_plugins') || function_exists('woact_core_get_settings')) {
            return;
        }
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('برای فعال‌شدن حالت ترکیبی و React، افزونهٔ Woact Core را نصب و فعال کنید. قالب در وضعیت فعلی با رندر معمول وردپرس کار می‌کند.', 'woact');
        echo '</p></div>';
    }

    private function fallback_bootstrap_data(): array
    {
        $post_id = is_singular() ? (int) get_queried_object_id() : 0;
        return [
            'version' => WOACT_THEME_VERSION,
            'site' => [
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => home_url('/'),
                'locale' => get_locale(),
                'direction' => is_rtl() ? 'rtl' : 'ltr',
            ],
            'route' => ['kind' => is_singular() ? 'singular' : 'archive', 'canonical' => home_url('/')],
            'navigation' => [],
            'content' => $post_id ? [
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'html' => apply_filters('the_content', (string) get_post_field('post_content', $post_id)),
            ] : ['title' => get_bloginfo('name'), 'items' => []],
        ];
    }
}

