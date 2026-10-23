<?php
/**
 * Plugin Name: Woact Core
 * Plugin URI: https://github.com/ostaddehdari/woact
 * Description: تنظیمات پایه، سیاست رندر و پل دادهٔ قالب React ووکت.
 * Version: 0.3.0-rc1
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Ostad Dehdari
 * Text Domain: woact-core
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WOACT_CORE_VERSION', '0.3.0-rc1');
define('WOACT_CORE_FILE', __FILE__);
define('WOACT_CORE_DIR', plugin_dir_path(__FILE__));

require_once WOACT_CORE_DIR . 'includes/class-woact-render-policy.php';
require_once WOACT_CORE_DIR . 'includes/class-woact-settings.php';
require_once WOACT_CORE_DIR . 'includes/class-woact-settings-rest-controller.php';
require_once WOACT_CORE_DIR . 'includes/class-woact-layouts.php';
require_once WOACT_CORE_DIR . 'includes/class-woact-layouts-rest-controller.php';
require_once WOACT_CORE_DIR . 'includes/class-woact-core.php';

register_activation_hook(__FILE__, ['Woact_Core', 'activate']);
add_action('plugins_loaded', static function (): void {
    Woact_Core::instance()->boot();
});

function woact_core_get_settings(): array
{
    return Woact_Core::instance()->get_settings();
}

function woact_core_get_request_render_mode(): string
{
    return Woact_Core::instance()->get_request_render_mode();
}

function woact_core_get_bootstrap_data(): array
{
    return Woact_Core::instance()->build_bootstrap_data();
}

function woact_core_has_layout(string $type): bool
{
    return Woact_Core::instance()->has_layout($type);
}

function woact_core_render_layout_root(string $type): string
{
    return Woact_Core::instance()->render_layout_root($type);
}

