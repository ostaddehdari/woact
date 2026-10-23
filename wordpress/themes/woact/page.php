<?php
/** Page template. @package Woact */
get_header();
?>
<main id="primary" class="woact-main woact-container">
    <?php while (have_posts()) : the_post(); ?>
        <?php get_template_part('template-parts/content', 'page'); ?>
        <?php if (comments_open() || get_comments_number()) { comments_template(); } ?>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>

