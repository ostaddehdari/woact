<?php
/**
 * Woact theme bootstrap.
 *
 * @package Woact
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WOACT_THEME_VERSION', '0.8.0-rc1');
define('WOACT_THEME_DIR', get_template_directory());
define('WOACT_THEME_URI', get_template_directory_uri());

require_once WOACT_THEME_DIR . '/inc/class-woact-theme.php';

Woact_Theme::instance()->boot();

