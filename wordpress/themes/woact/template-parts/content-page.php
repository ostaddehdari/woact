<?php
/** Page content. @package Woact */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('woact-entry'); ?>>
    <?php if (!is_front_page()) : ?><h1 class="woact-entry-title"><?php the_title(); ?></h1><?php endif; ?>
    <div class="woact-entry-content">
        <?php the_content(); ?>
        <?php wp_link_pages(); ?>
    </div>
</article>

