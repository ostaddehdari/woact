<?php
/** Single content. @package Woact */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('woact-entry'); ?>>
    <header>
        <h1 class="woact-entry-title"><?php the_title(); ?></h1>
        <div class="woact-entry-meta"><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time></div>
    </header>
    <?php if (has_post_thumbnail()) { the_post_thumbnail('large'); } ?>
    <div class="woact-entry-content"><?php the_content(); wp_link_pages(); ?></div>
</article>

