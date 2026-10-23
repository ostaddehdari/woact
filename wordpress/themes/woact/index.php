<?php
/** Main fallback template. @package Woact */
get_header();
?>
<main id="primary" class="woact-main woact-container">
    <?php if (have_posts()) : ?>
        <div class="woact-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/content', get_post_type()); ?>
            <?php endwhile; ?>
        </div>
        <div class="woact-pagination"><?php the_posts_pagination(); ?></div>
    <?php else : ?>
        <?php get_template_part('template-parts/content', 'none'); ?>
    <?php endif; ?>
</main>
<?php get_footer(); ?>

