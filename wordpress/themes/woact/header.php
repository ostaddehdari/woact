<?php
/** @package Woact */
$woact_login_url = wp_login_url(home_url('/'));
foreach (['login', 'ورود'] as $woact_login_slug) {
    $woact_login_page = get_page_by_path($woact_login_slug);
    if ($woact_login_page instanceof WP_Post && $woact_login_page->post_status === 'publish') {
        $woact_login_url = get_permalink($woact_login_page);
        break;
    }
}
$woact_login_url = apply_filters('woact_frontend_login_url', $woact_login_url);
$woact_account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
$woact_cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '';
$woact_logged_in = is_user_logged_in();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#primary"><?php esc_html_e('رفتن به محتوا', 'woact'); ?></a>
<?php if (function_exists('woact_core_has_layout') && woact_core_has_layout('header')) : ?>
    <?php echo woact_core_render_layout_root('header'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php else : ?>
<header class="woact-site-header" role="banner">
    <div class="woact-container woact-header-inner">
        <div class="woact-brand">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
            <?php endif; ?>
        </div>
        <nav class="woact-nav woact-desktop-nav" aria-label="<?php esc_attr_e('منوی اصلی', 'woact'); ?>">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => false]); ?>
        </nav>
        <div class="woact-header-actions">
            <?php if (!$woact_logged_in) : ?>
                <a class="woact-icon-button woact-login-button" href="<?php echo esc_url($woact_login_url); ?>" aria-label="<?php esc_attr_e('ورود به حساب کاربری', 'woact'); ?>" title="<?php esc_attr_e('ورود', 'woact'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.5 3-7 8-7s8 2.5 8 7"/></svg>
                </a>
            <?php else : ?>
                <details class="woact-header-search">
                    <summary class="woact-icon-button" aria-label="<?php esc_attr_e('باز کردن جست‌وجو', 'woact'); ?>" title="<?php esc_attr_e('جست‌وجو', 'woact'); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.7" cy="10.7" r="6.7"/><path d="m16 16 5 5"/></svg>
                    </summary>
                    <div class="woact-search-panel"><?php get_search_form(); ?></div>
                </details>
                <?php if ($woact_cart_url) : ?>
                    <a class="woact-icon-button" href="<?php echo esc_url($woact_cart_url); ?>" aria-label="<?php esc_attr_e('سبد خرید', 'woact'); ?>" title="<?php esc_attr_e('سبد خرید', 'woact'); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.2 11h11.6L21 7H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                    </a>
                <?php endif; ?>
                <a class="woact-account-link" href="<?php echo esc_url($woact_account_url); ?>"><?php esc_html_e('حساب من', 'woact'); ?></a>
            <?php endif; ?>
        </div>
        <details class="woact-mobile-menu">
            <summary aria-label="<?php esc_attr_e('باز کردن منو', 'woact'); ?>">
                <span class="woact-burger" aria-hidden="true"><span></span><span></span><span></span></span>
            </summary>
            <nav aria-label="<?php esc_attr_e('منوی موبایل', 'woact'); ?>">
                <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => false]); ?>
                <?php if ($woact_logged_in) : ?>
                    <a href="<?php echo esc_url($woact_account_url); ?>"><?php esc_html_e('پنل کاربری', 'woact'); ?></a>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><?php esc_html_e('خروج از حساب', 'woact'); ?></a>
                <?php else : ?>
                    <a href="<?php echo esc_url($woact_login_url); ?>"><?php esc_html_e('ورود', 'woact'); ?></a>
                <?php endif; ?>
            </nav>
        </details>
    </div>
</header>
<?php endif; ?>
