<?php
/** Empty state. @package Woact */
?>
<section class="woact-empty">
    <h1><?php esc_html_e('محتوایی پیدا نشد', 'woact'); ?></h1>
    <?php if (is_search()) : ?>
        <p><?php esc_html_e('عبارت دیگری را جست‌وجو کنید.', 'woact'); ?></p>
        <?php get_search_form(); ?>
    <?php endif; ?>
</section>

