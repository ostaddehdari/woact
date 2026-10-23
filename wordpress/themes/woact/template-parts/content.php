<?php
/** Generic content card. @package Woact */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('woact-card'); ?>>
    <?php if (has_post_thumbnail()) : ?>
        <a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail('large'); ?></a>
    <?php endif; ?>
    <h2 class="woact-entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <div class="woact-entry-meta"><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time></div>
    <div><?php the_excerpt(); ?></div>
</article>

