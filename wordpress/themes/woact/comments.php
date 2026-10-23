<?php
/** Comments template. @package Woact */
if (post_password_required()) {
    return;
}
?>
<section id="comments" class="woact-entry">
    <?php if (have_comments()) : ?>
        <h2><?php echo esc_html(sprintf(_n('%s دیدگاه', '%s دیدگاه', get_comments_number(), 'woact'), number_format_i18n(get_comments_number()))); ?></h2>
        <ol><?php wp_list_comments(['style' => 'ol', 'short_ping' => true]); ?></ol>
        <?php the_comments_navigation(); ?>
    <?php endif; ?>
    <?php comment_form(); ?>
</section>

