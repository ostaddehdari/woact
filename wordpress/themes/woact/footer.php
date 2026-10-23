<?php
/** @package Woact */
?>
<?php
$woact_footer_enabled = true;
$woact_footer_settings = [];
if (function_exists('woact_core_get_settings')) {
    $woact_footer_settings = woact_core_get_settings();
    $woact_footer_enabled = !isset($woact_footer_settings['footer.enabled']) || !empty($woact_footer_settings['footer.enabled']);
}
?>
<?php if ($woact_footer_enabled) : ?>
<footer class="woact-site-footer">
    <div class="woact-container woact-footer-inner">
        <?php
        $woact_footer_text = sprintf(__('© %1$s %2$s', 'woact'), wp_date('Y'), get_bloginfo('name'));
        if (!empty($woact_footer_settings['footer.copyright'])) {
            $woact_footer_text = strtr((string) $woact_footer_settings['footer.copyright'], [
                '{year}' => wp_date('Y'),
                '{site_name}' => get_bloginfo('name'),
            ]);
        }
        ?>
        <span><?php echo esc_html($woact_footer_text); ?></span>
        <nav class="woact-nav" aria-label="<?php esc_attr_e('منوی فوتر', 'woact'); ?>">
            <?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'fallback_cb' => false]); ?>
        </nav>
    </div>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>

