<?php
/** @package Woact */
?>
<footer class="woact-site-footer">
    <div class="woact-container woact-footer-inner">
        <span><?php echo esc_html(sprintf(__('© %1$s %2$s', 'woact'), wp_date('Y'), get_bloginfo('name'))); ?></span>
        <nav class="woact-nav" aria-label="<?php esc_attr_e('منوی فوتر', 'woact'); ?>">
            <?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'fallback_cb' => false]); ?>
        </nav>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

