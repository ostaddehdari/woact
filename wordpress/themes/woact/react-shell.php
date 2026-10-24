<?php
/**
 * React document shell with an HTML fallback for crawlers and disabled JavaScript.
 *
 * @package Woact
 */

get_header('react');
$woact_page_fallback = is_page() && function_exists('woact_core_render_page_fallback')
    ? woact_core_render_page_fallback((int) get_queried_object_id())
    : '';
?>
<div id="woact-root" data-woact-version="<?php echo esc_attr(WOACT_THEME_VERSION); ?>">
    <?php if (function_exists('woact_core_has_layout') && woact_core_has_layout('header')) : ?>
        <?php echo woact_core_render_layout_root('header'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
    <header class="woact-react-header">
        <div class="woact-container woact-react-header__inner">
            <a class="woact-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
        </div>
    </header>
    <?php endif; ?>
    <main id="woact-main" class="woact-main<?php echo $woact_page_fallback !== '' ? '' : ' woact-container'; ?>" data-woact-fallback>
        <?php if ($woact_page_fallback !== '') : ?>
            <?php echo $woact_page_fallback; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php elseif (is_404()) : ?>
            <section class="woact-empty">
                <h1><?php esc_html_e('صفحه پیدا نشد', 'woact'); ?></h1>
            </section>
        <?php elseif (have_posts()) : ?>
            <?php if (!is_singular()) : ?><div class="woact-grid"><?php endif; ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/content', is_singular() ? (is_page() ? 'page' : 'single') : get_post_type()); ?>
            <?php endwhile; ?>
            <?php if (!is_singular()) : ?></div><?php endif; ?>
        <?php else : ?>
            <?php get_template_part('template-parts/content', 'none'); ?>
        <?php endif; ?>
    </main>
    <?php if (function_exists('woact_core_has_layout') && woact_core_has_layout('footer')) : ?>
        <?php echo woact_core_render_layout_root('footer'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
    <footer class="woact-react-footer">
        <div class="woact-container"><?php echo esc_html(sprintf(__('© %1$s %2$s', 'woact'), wp_date('Y'), get_bloginfo('name'))); ?></div>
    </footer>
    <?php endif; ?>
</div>
<?php get_footer('react'); ?>

