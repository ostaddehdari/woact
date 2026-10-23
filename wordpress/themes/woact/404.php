<?php
/** 404 template. @package Woact */
get_header();
?>
<main id="primary" class="woact-main woact-container">
    <section class="woact-empty">
        <h1><?php esc_html_e('صفحه پیدا نشد', 'woact'); ?></h1>
        <p><?php esc_html_e('نشانی را بررسی کنید یا از جست‌وجو استفاده کنید.', 'woact'); ?></p>
        <?php get_search_form(); ?>
    </section>
</main>
<?php get_footer(); ?>

