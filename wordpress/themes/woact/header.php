<?php
/** @package Woact */
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
<header class="woact-site-header">
    <div class="woact-container woact-header-inner">
        <div class="woact-brand">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
            <?php endif; ?>
        </div>
        <nav class="woact-nav" aria-label="<?php esc_attr_e('منوی اصلی', 'woact'); ?>">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => false]); ?>
        </nav>
    </div>
</header>
<?php endif; ?>

