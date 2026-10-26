<?php
declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WOACT_TESTING')) {
    exit;
}

/** Versioned storage and validation for React page-builder documents. */
final class Woact_Pages
{
    public const SCHEMA_VERSION = 1;
    public const META_DRAFT = '_woact_page_draft';
    public const META_PUBLISHED = '_woact_page_published';
    public const META_REVISIONS = '_woact_page_revisions';
    public const META_ENABLED = '_woact_page_enabled';
    private const NODE_TYPES = ['section', 'container', 'columns', 'column', 'heading', 'text', 'button', 'image', 'icon', 'list', 'quote', 'divider', 'spacer', 'html'];
    private const CONTAINERS = ['section', 'container', 'columns', 'column'];
    private ?Woact_Widget_Registry $widgets;
    private array $prop_keys = [];

    public function __construct(?Woact_Widget_Registry $widgets = null)
    {
        $this->widgets = $widgets;
    }

    public function get_manifest(): array
    {
        $field = static fn(string $id, string $label, string $type = 'text', $default = '', array $extra = []): array => array_merge(compact('id', 'label', 'type', 'default'), $extra);
        $manifest = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'categories' => [
                ['id' => 'layout', 'label' => 'چیدمان'],
                ['id' => 'basic', 'label' => 'پایه'],
                ['id' => 'media', 'label' => 'رسانه'],
            ],
            'components' => [
                ['type' => 'section', 'label' => 'بخش', 'icon' => 'align-wide', 'category' => 'layout', 'container' => true, 'defaults' => ['tag' => 'section'], 'fields' => [$field('tag', 'تگ HTML', 'select', 'section', ['options' => ['section', 'header', 'footer', 'main', 'article', 'aside']])]],
                ['type' => 'container', 'label' => 'کانتینر', 'icon' => 'editor-table', 'category' => 'layout', 'container' => true, 'defaults' => [], 'fields' => []],
                ['type' => 'columns', 'label' => 'ستون‌ها', 'icon' => 'columns', 'category' => 'layout', 'container' => true, 'defaults' => [], 'fields' => []],
                ['type' => 'column', 'label' => 'ستون', 'icon' => 'excerpt-view', 'category' => 'layout', 'container' => true, 'defaults' => ['basis' => 6], 'fields' => [$field('basis', 'عرض از ۱۲', 'slider', 6, ['min' => 1, 'max' => 12])]],
                ['type' => 'heading', 'label' => 'عنوان', 'icon' => 'heading', 'category' => 'basic', 'defaults' => ['text' => 'عنوان جدید', 'level' => 'h2'], 'fields' => [$field('text', 'متن', 'textarea'), $field('level', 'سطح عنوان', 'button_group', 'h2', ['options' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']])]],
                ['type' => 'text', 'label' => 'متن', 'icon' => 'editor-paragraph', 'category' => 'basic', 'defaults' => ['text' => 'متن خود را اینجا بنویسید.'], 'fields' => [$field('text', 'متن', 'textarea')]],
                ['type' => 'button', 'label' => 'دکمه', 'icon' => 'button', 'category' => 'basic', 'defaults' => ['label' => 'بیشتر بدانید', 'url' => '#', 'variant' => 'primary'], 'fields' => [$field('label', 'عنوان'), $field('url', 'پیوند', 'url'), $field('variant', 'نوع', 'button_group', 'primary', ['options' => [['value' => 'primary', 'label' => 'اصلی'], ['value' => 'outline', 'label' => 'خطی']]])]],
                ['type' => 'image', 'label' => 'تصویر', 'icon' => 'format-image', 'category' => 'media', 'defaults' => ['url' => '', 'alt' => '', 'caption' => ''], 'fields' => [$field('url', 'تصویر', 'media'), $field('alt', 'متن جایگزین'), $field('caption', 'زیرنویس')]],
                ['type' => 'icon', 'label' => 'آیکون', 'icon' => 'star-filled', 'category' => 'basic', 'defaults' => ['icon' => '★'], 'fields' => [$field('icon', 'آیکون')]],
                ['type' => 'list', 'label' => 'فهرست', 'icon' => 'editor-ul', 'category' => 'basic', 'defaults' => ['items' => "مورد اول\nمورد دوم\nمورد سوم"], 'fields' => [$field('items', 'هر مورد در یک خط', 'textarea')]],
                ['type' => 'quote', 'label' => 'نقل‌قول', 'icon' => 'format-quote', 'category' => 'basic', 'defaults' => ['text' => 'متن نقل‌قول', 'author' => ''], 'fields' => [$field('text', 'متن', 'textarea'), $field('author', 'نویسنده')]],
                ['type' => 'divider', 'label' => 'جداکننده', 'icon' => 'minus', 'category' => 'layout', 'defaults' => [], 'fields' => []],
                ['type' => 'spacer', 'label' => 'فاصله', 'icon' => 'leftright', 'category' => 'layout', 'defaults' => ['height' => 40], 'fields' => [$field('height', 'ارتفاع', 'slider', 40, ['min' => 0, 'max' => 500])]],
                ['type' => 'html', 'label' => 'HTML امن', 'icon' => 'editor-code', 'category' => 'basic', 'defaults' => ['html' => ''], 'fields' => [$field('html', 'HTML', 'code')]],
            ],
            'breakpoints' => ['desktop' => 1200, 'tablet' => 782, 'mobile' => 480],
            'controls' => Woact_Control_Schema::manifest(),
            'dynamicSources' => [
                ['value' => '', 'label' => 'بدون داده پویا'],
                ['value' => 'post.title', 'label' => 'عنوان نوشته'],
                ['value' => 'post.excerpt', 'label' => 'خلاصه نوشته'],
                ['value' => 'post.featuredImage', 'label' => 'تصویر شاخص'],
                ['value' => 'site.name', 'label' => 'نام سایت'],
                ['value' => 'site.description', 'label' => 'توضیحات سایت'],
                ['value' => 'user.name', 'label' => 'نام کاربر'],
            ],
        ];
        if ($this->widgets) {
            $manifest['categories'] = array_merge($manifest['categories'], $this->widgets->categories());
            $manifest['components'] = array_merge($manifest['components'], $this->widgets->definitions());
        }
        return $manifest;
    }

    public function list_pages(): array
    {
        $query = new WP_Query([
            'post_type' => ['page', 'post'], 'post_status' => ['publish', 'draft', 'private'], 'posts_per_page' => 200,
            'orderby' => 'modified', 'order' => 'DESC', 'no_found_rows' => true,
        ]);
        $editable = array_values(array_filter($query->posts, static fn(WP_Post $post): bool => current_user_can('edit_post', $post->ID)));
        return array_map(function (WP_Post $post): array {
            $draft = get_post_meta($post->ID, self::META_DRAFT, true);
            return [
                'id' => (int) $post->ID, 'postType' => $post->post_type,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'enabled' => get_post_meta($post->ID, self::META_ENABLED, true) === '1',
                'hasDocument' => is_array($draft),
                'editUrl' => get_edit_post_link($post->ID, 'raw'),
                'viewUrl' => get_permalink($post->ID),
            ];
        }, $editable);
    }

    public function create(string $title, int $user_id, string $post_type = 'page')
    {
        $post_type = in_array($post_type, ['post', 'page'], true) ? $post_type : 'page';
        $post_id = wp_insert_post([
            'post_type' => $post_type, 'post_status' => 'draft', 'post_title' => sanitize_text_field($title ?: 'محتوای React جدید'),
            'post_author' => $user_id,
        ], true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        $document = $this->blank_document();
        update_post_meta($post_id, self::META_DRAFT, $document);
        update_post_meta($post_id, self::META_ENABLED, '1');
        update_post_meta($post_id, '_woact_render_mode', 'react');
        return $this->get((int) $post_id);
    }

    public function get(int $post_id): ?array
    {
        $post = get_post($post_id);
        if (!$post instanceof WP_Post || !in_array($post->post_type, ['page', 'post'], true)) {
            return null;
        }
        $draft = get_post_meta($post_id, self::META_DRAFT, true);
        $published = get_post_meta($post_id, self::META_PUBLISHED, true);
        $revisions = get_post_meta($post_id, self::META_REVISIONS, true);
        $draft = is_array($draft) ? $this->sanitize_document($draft) : $this->blank_document();
        $published = is_array($published) ? $this->sanitize_document($published) : null;
        $revisions = is_array($revisions) ? array_values(array_filter($revisions, 'is_array')) : [];
        return [
            'id' => $post_id, 'postType' => $post->post_type, 'title' => $post->post_title, 'status' => $post->post_status,
            'enabled' => get_post_meta($post_id, self::META_ENABLED, true) === '1',
            'draft' => $draft, 'published' => $published,
            'revisions' => $revisions,
            'revision' => $this->revision_token($post_id, $draft),
            'editUrl' => get_edit_post_link($post_id, 'raw'),
            'viewUrl' => get_permalink($post_id),
        ];
    }

    public function save(int $post_id, array $input, string $expected_revision)
    {
        $current = $this->get($post_id);
        if (!$current) {
            return new WP_Error('woact_page_missing', __('برگه پیدا نشد.', 'woact-core'), ['status' => 404]);
        }
        $conflict_resolved = $expected_revision !== '' && !hash_equals($current['revision'], $expected_revision);
        if ($conflict_resolved) $this->archive_document($post_id, (array) $current['draft'], get_current_user_id(), 'conflict');
        $document = $this->sanitize_document((array) ($input['document'] ?? []));
        update_post_meta($post_id, self::META_DRAFT, $document);
        update_post_meta($post_id, self::META_ENABLED, !empty($input['enabled']) ? '1' : '0');
        if (isset($input['title'])) {
            wp_update_post(['ID' => $post_id, 'post_title' => sanitize_text_field((string) $input['title'])]);
        }
        clean_post_cache($post_id);
        $saved = $this->get($post_id);
        if ($saved) $saved['conflictResolved'] = $conflict_resolved;
        return $saved;
    }

    private function archive_document(int $post_id, array $document, int $user_id, string $reason = 'manual'): void
    {
        $revisions = get_post_meta($post_id, self::META_REVISIONS, true);
        $revisions = is_array($revisions) ? array_values(array_filter($revisions, 'is_array')) : [];
        array_unshift($revisions, [
            'id' => wp_generate_uuid4(), 'createdAt' => time(), 'userId' => $user_id,
            'reason' => sanitize_key($reason), 'document' => $this->sanitize_document($document),
        ]);
        update_post_meta($post_id, self::META_REVISIONS, array_slice($revisions, 0, 30));
    }

    public function publish(int $post_id, int $user_id)
    {
        $current = $this->get($post_id);
        if (!$current) {
            return new WP_Error('woact_page_missing', __('برگه پیدا نشد.', 'woact-core'), ['status' => 404]);
        }
        $revisions = get_post_meta($post_id, self::META_REVISIONS, true);
        $revisions = is_array($revisions) ? array_values(array_filter($revisions, 'is_array')) : [];
        if (is_array($current['published'])) {
            array_unshift($revisions, ['id' => wp_generate_uuid4(), 'createdAt' => time(), 'userId' => $user_id, 'document' => $current['published']]);
            $revisions = array_slice($revisions, 0, 30);
            update_post_meta($post_id, self::META_REVISIONS, $revisions);
        }
        update_post_meta($post_id, self::META_PUBLISHED, $current['draft']);
        update_post_meta($post_id, self::META_ENABLED, '1');
        update_post_meta($post_id, '_woact_render_mode', 'react');
        wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);
        clean_post_cache($post_id);
        return $this->get($post_id);
    }

    public function restore(int $post_id, string $revision_id)
    {
        $revisions = (array) get_post_meta($post_id, self::META_REVISIONS, true);
        foreach ($revisions as $revision) {
            if (($revision['id'] ?? '') === $revision_id && is_array($revision['document'] ?? null)) {
                $document = $this->sanitize_document($revision['document']);
                update_post_meta($post_id, self::META_DRAFT, $document);
                update_post_meta($post_id, self::META_PUBLISHED, $document);
                return $this->get($post_id);
            }
        }
        return new WP_Error('woact_page_revision_missing', __('نسخه پیدا نشد.', 'woact-core'), ['status' => 404]);
    }

    public function get_runtime_document(int $post_id): ?array
    {
        if (get_post_meta($post_id, self::META_ENABLED, true) !== '1') {
            return null;
        }
        $document = get_post_meta($post_id, self::META_PUBLISHED, true);
        return is_array($document) ? $this->sanitize_document($document) : null;
    }

    public function render_fallback(array $document, array $data = []): string
    {
        $globals = (array) ($document['settings']['designSystem'] ?? []);
        $custom_css = (string) ($globals['customCss'] ?? '') . "\n" . $this->collect_custom_css((array) ($document['nodes'] ?? []));
        $html = $custom_css !== '' ? '<style>' . $custom_css . '</style>' : '';
        $variables = ''; foreach ((array) ($globals['colors'] ?? []) as $item) $variables .= '--woact-color-' . sanitize_key((string) ($item['id'] ?? '')) . ':' . esc_attr((string) ($item['value'] ?? '')) . ';';
        $html .= '<div class="woact-page-runtime" style="--woact-page-width:' . (int) ($document['settings']['contentWidth'] ?? 1180) . 'px;background:' . esc_attr((string) ($document['settings']['background'] ?? '#ffffff')) . ';' . $variables . '">';
        $data['designSystem'] = $globals;
        foreach ((array) ($document['nodes'] ?? []) as $node) {
            $html .= $this->render_node((array) $node, $data);
        }
        return $html . '</div>';
    }

    private function render_node(array $node, array $data): string
    {
        if (isset($node['visibility']['desktop']) && !$node['visibility']['desktop']) return '';
        $type = (string) ($node['type'] ?? '');
        $props = (array) ($node['props'] ?? []);
        $style = (array) ($node['style'] ?? []);
        $css = 'display:' . esc_attr((string) ($style['display'] ?? 'block')) . ';';
        foreach (['gap', 'padding', 'margin', 'width', 'maxWidth', 'minHeight', 'fontSize', 'radius', 'borderWidth'] as $key) {
            if (!empty($style[$key])) {
                $property = ['maxWidth' => 'max-width', 'minHeight' => 'min-height', 'fontSize' => 'font-size', 'radius' => 'border-radius', 'borderWidth' => 'border-width'][$key] ?? strtolower($key);
                $css .= $property . ':' . (int) $style[$key] . 'px;';
            }
        }
        foreach (['background', 'color', 'borderColor'] as $key) {
            if (!empty($style[$key])) $css .= ($key === 'borderColor' ? 'border-color' : $key) . ':' . esc_attr((string) $style[$key]) . ';';
        }
        if (!empty($style['fontWeight'])) $css .= 'font-weight:' . (int) $style['fontWeight'] . ';';
        if (!empty($style['direction'])) $css .= 'flex-direction:' . esc_attr((string) $style['direction']) . ';';
        if (!empty($style['borderWidth'])) $css .= 'border-style:solid;';
        $design = (array) ($node['design'] ?? []);
        $globals = (array) ($data['designSystem'] ?? []);
        $preset = $this->design_token((array) ($globals['styles'] ?? []), (string) ($design['globalStyle'] ?? ''));
        $resolved_design = array_merge($preset ?: [], $design);
        $css .= $this->design_css($resolved_design, $globals);
        $class = 'woact-page-node woact-page-node--' . esc_attr($type);
        if (!empty($design['entranceAnimation'])) $class .= ' woact-animate woact-' . sanitize_html_class((string) $design['entranceAnimation']);
        if (!empty($design['hoverAnimation'])) $class .= ' woact-hover-' . sanitize_html_class((string) $design['hoverAnimation']);
        if (!empty($preset['className'])) $class .= ' ' . esc_attr((string) $preset['className']);
        if (!empty($design['cssClasses'])) $class .= ' ' . esc_attr((string) $design['cssClasses']);
        $html_id = !empty($design['cssId']) ? ' id="' . esc_attr((string) $design['cssId']) . '"' : '';
        $attributes = $this->design_attributes($design);
        $common = $html_id . ' class="' . $class . '" data-woact-node="' . esc_attr((string) ($node['id'] ?? '')) . '" style="' . $css . '"' . $attributes;
        $dynamic = $this->dynamic_value((string) ($node['dynamic'] ?? ''), $data);
        if (in_array($type, self::CONTAINERS, true)) {
            $tag = $type === 'section' && in_array(($props['tag'] ?? ''), ['section', 'header', 'footer', 'main', 'article', 'aside'], true) ? $props['tag'] : 'div';
            $inner = '';
            foreach ((array) ($node['children'] ?? []) as $child) $inner .= $this->render_node((array) $child, $data);
            return '<' . $tag . $common . '>' . $inner . '</' . $tag . '>';
        }
        if ($type === 'heading') { $tag = preg_match('/^h[1-6]$/', (string) ($props['level'] ?? '')) ? $props['level'] : 'h2'; return '<' . $tag . $common . '>' . esc_html((string) ($dynamic ?? $props['text'] ?? '')) . '</' . $tag . '>'; }
        if ($type === 'text') return '<p' . $common . '>' . esc_html((string) ($dynamic ?? $props['text'] ?? '')) . '</p>';
        if ($type === 'button') return '<a' . str_replace('class="' . $class . '"', 'class="' . $class . ' is-' . esc_attr((string) ($props['variant'] ?? 'primary')) . '"', $common) . ' href="' . esc_url((string) ($props['url'] ?? '#')) . '">' . esc_html((string) ($props['label'] ?? '')) . '</a>';
        if ($type === 'image') { $src = (string) ($dynamic ?? $props['url'] ?? ''); return '<figure' . $common . '>' . ($src ? '<img src="' . esc_url($src) . '" alt="' . esc_attr((string) ($props['alt'] ?? '')) . '">' : '') . '</figure>'; }
        if ($type === 'html') return '<div' . $common . '>' . $this->sanitize_html_widget((string) ($props['html'] ?? '')) . '</div>';
        if ($type === 'divider') return '<hr' . $common . '>';
        if ($type === 'spacer') return '<div' . str_replace('style="' . $css . '"', 'style="' . $css . 'height:' . (int) ($props['height'] ?? 40) . 'px"', $common) . '></div>';
        $text = (string) ($props['icon'] ?? $props['text'] ?? '');
        return '<div' . $common . '>' . esc_html($text) . '</div>';
    }

    private function design_css(array $design, array $globals = []): string
    {
        $css = '';
        $color = $this->design_token((array) ($globals['colors'] ?? []), (string) ($design['colorToken'] ?? '')); if ($color) $css .= 'color:' . esc_attr((string) ($color['value'] ?? '')) . ';';
        $background_token = $this->design_token((array) ($globals['colors'] ?? []), (string) ($design['backgroundToken'] ?? '')); if ($background_token) $css .= 'background-color:' . esc_attr((string) ($background_token['value'] ?? '')) . ';';
        $spacing = $this->design_token((array) ($globals['spacing'] ?? []), (string) ($design['paddingToken'] ?? '')); if ($spacing) $css .= 'padding:' . (float) ($spacing['value'] ?? 0) . 'px;';
        $radius = $this->design_token((array) ($globals['radii'] ?? []), (string) ($design['radiusToken'] ?? '')); if ($radius) $css .= 'border-radius:' . (float) ($radius['value'] ?? 0) . 'px;';
        $shadow = $this->design_token((array) ($globals['shadows'] ?? []), (string) ($design['shadowToken'] ?? '')); if ($shadow) $css .= 'box-shadow:' . esc_attr((string) ($shadow['value'] ?? '')) . ';';
        $typography = $this->design_token((array) ($globals['typography'] ?? []), (string) ($design['typographyToken'] ?? '')); if ($typography) $css .= 'font-family:' . esc_attr((string) ($typography['family'] ?? '')) . ';font-size:' . (float) ($typography['size'] ?? 0) . 'px;font-weight:' . (int) ($typography['weight'] ?? 400) . ';line-height:' . (float) ($typography['lineHeight'] ?? 1.5) . ';';
        foreach (['marginBox' => 'margin', 'paddingBox' => 'padding'] as $key => $property) {
            $box = (array) ($design[$key] ?? []); if (!$box) continue; $unit = in_array(($box['unit'] ?? ''), ['px', '%', 'em', 'rem', 'vw', 'vh'], true) ? $box['unit'] : 'px';
            $css .= $property . ':' . (float) ($box['top'] ?? 0) . $unit . ' ' . (float) ($box['right'] ?? 0) . $unit . ' ' . (float) ($box['bottom'] ?? 0) . $unit . ' ' . (float) ($box['left'] ?? 0) . $unit . ';';
        }
        foreach (['widthUnit' => 'width', 'minHeightUnit' => 'min-height'] as $key => $property) { $value = (array) ($design[$key] ?? []); if ($value) $css .= $property . ':' . (float) ($value['value'] ?? 0) . esc_attr((string) ($value['unit'] ?? 'px')) . ';'; }
        if (!empty($design['position'])) $css .= 'position:' . esc_attr((string) $design['position']) . ';';
        if (isset($design['zIndex'])) $css .= 'z-index:' . (int) $design['zIndex'] . ';';
        $background = (array) ($design['backgroundControl'] ?? []); if (!empty($background['color'])) $css .= 'background-color:' . esc_attr((string) $background['color']) . ';'; if (!empty($background['image'])) $css .= 'background-image:url(' . esc_url((string) $background['image']) . ');background-size:' . esc_attr((string) ($background['size'] ?? 'cover')) . ';';
        $mask = ['circle' => 'circle(50%)', 'ellipse' => 'ellipse(50% 40%)', 'triangle' => 'polygon(50% 0,100% 100%,0 100%)', 'diamond' => 'polygon(50% 0,100% 50%,50% 100%,0 50%)', 'hexagon' => 'polygon(25% 6%,75% 6%,100% 50%,75% 94%,25% 94%,0 50%)', 'blob' => 'polygon(46% 2%,86% 15%,100% 55%,76% 91%,33% 100%,5% 70%,8% 27%)']; if (!empty($mask[$design['maskShape'] ?? ''])) $css .= 'clip-path:' . $mask[$design['maskShape']] . ';';
        if (!empty($design['animationDuration'])) $css .= 'animation-duration:' . (int) $design['animationDuration'] . 'ms;'; if (!empty($design['animationDelay'])) $css .= 'animation-delay:' . (int) $design['animationDelay'] . 'ms;';
        return $css;
    }

    private function design_token(array $items, string $id): ?array
    {
        if ($id === '') return null; foreach ($items as $item) if (is_array($item) && ($item['id'] ?? '') === $id) return $item; return null;
    }

    private function design_attributes(array $design): string
    {
        $html = ''; foreach ((array) ($design['attributes'] ?? []) as $attribute) { if (!is_array($attribute)) continue; $key = (string) ($attribute['key'] ?? ''); if (!preg_match('/^(data|aria)-[a-z0-9_-]+$/', $key) && !in_array($key, ['title', 'role', 'tabindex'], true)) continue; $html .= ' ' . esc_attr($key) . '="' . esc_attr((string) ($attribute['value'] ?? '')) . '"'; } return $html;
    }

    private function collect_custom_css(array $nodes): string
    {
        $css = ''; foreach ($nodes as $node) { if (!is_array($node)) continue; $selector = '[data-woact-node="' . $this->clean_id((string) ($node['id'] ?? '')) . '"]'; $custom = (string) ($node['design']['customCss'] ?? ''); if ($custom !== '') $css .= str_replace('selector', $selector, $custom) . "\n"; $css .= $this->collect_custom_css((array) ($node['children'] ?? [])); } return $css;
    }

    private function dynamic_value(string $path, array $data)
    {
        if ($path === '') return null;
        $value = $data;
        foreach (explode('.', $path) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return null;
            $value = $value[$part];
        }
        return is_scalar($value) ? $value : null;
    }

    public function sanitize_document(array $document): array
    {
        $settings = is_array($document['settings'] ?? null) ? $document['settings'] : [];
        $nodes = [];
        foreach (array_slice((array) ($document['nodes'] ?? []), 0, 100) as $index => $node) {
            $clean = $this->sanitize_node($node, 0, (string) $index);
            if ($clean) {
                $nodes[] = $clean;
            }
        }
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'settings' => [
                'contentWidth' => max(320, min(1920, (int) ($settings['contentWidth'] ?? 1180))),
                'background' => $this->color((string) ($settings['background'] ?? '#ffffff'), '#ffffff'),
                'className' => sanitize_html_class((string) ($settings['className'] ?? '')),
                'hideTitle' => !empty($settings['hideTitle']),
                'designSystem' => $this->sanitize_design_system((array) ($settings['designSystem'] ?? [])),
            ],
            'nodes' => $nodes ?: $this->blank_document()['nodes'],
        ];
    }

    private function sanitize_node($node, int $depth, string $path = '0'): ?array
    {
        if (!is_array($node) || $depth > 8 || !$this->is_allowed_type((string) ($node['type'] ?? ''))) {
            return null;
        }
        $type = (string) $node['type'];
        $props = [];
        foreach ((array) ($node['props'] ?? []) as $key => $value) {
            $key = $this->canonical_prop_key($type, (string) $key);
            if ($key === '') continue;
            if ($key === 'html' && $type === 'html') {
                $props[$key] = $this->sanitize_html_widget((string) $value);
            } else {
                $props[$key] = $this->sanitize_prop_value($value, $key, 0);
            }
        }
        $children = [];
        if (in_array($type, self::CONTAINERS, true)) {
            foreach (array_slice((array) ($node['children'] ?? []), 0, 50) as $index => $child) {
                $clean = $this->sanitize_node($child, $depth + 1, $path . '-' . $index);
                if ($clean) {
                    $children[] = $clean;
                }
            }
        }
        return [
            'id' => $this->stable_node_id((string) ($node['id'] ?? ''), $type, $path),
            'type' => $type,
            'props' => $props,
            'style' => $this->sanitize_style((array) ($node['style'] ?? [])),
            'responsive' => [
                'tablet' => $this->sanitize_style((array) ($node['responsive']['tablet'] ?? []), true),
                'mobile' => $this->sanitize_style((array) ($node['responsive']['mobile'] ?? []), true),
            ],
            'design' => $this->sanitize_design((array) ($node['design'] ?? [])),
            'visibility' => [
                'desktop' => !isset($node['visibility']['desktop']) || !empty($node['visibility']['desktop']),
                'tablet' => !isset($node['visibility']['tablet']) || !empty($node['visibility']['tablet']),
                'mobile' => !isset($node['visibility']['mobile']) || !empty($node['visibility']['mobile']),
            ],
            'dynamic' => sanitize_text_field((string) ($node['dynamic'] ?? '')),
            'locked' => !empty($node['locked']),
            'children' => $children,
        ];
    }

    private function sanitize_style(array $style, bool $partial = false): array
    {
        $defaults = $partial ? [] : ['display' => 'flex', 'direction' => 'column', 'gap' => 16, 'padding' => 0, 'margin' => 0, 'background' => '', 'color' => '', 'width' => 0, 'maxWidth' => 0, 'minHeight' => 0, 'align' => 'stretch', 'justify' => 'start', 'fontSize' => 0, 'fontWeight' => 0, 'radius' => 0, 'borderWidth' => 0, 'borderColor' => '', 'shadow' => '', 'zIndex' => 0];
        $out = $defaults;
        $enums = ['display' => ['flex', 'grid', 'block'], 'direction' => ['row', 'column'], 'align' => ['start', 'center', 'end', 'stretch'], 'justify' => ['start', 'center', 'end', 'between']];
        foreach ($enums as $key => $allowed) {
            if (array_key_exists($key, $style) && in_array($style[$key], $allowed, true)) {
                $out[$key] = $style[$key];
            }
        }
        foreach (['gap' => 100, 'padding' => 200, 'margin' => 200, 'width' => 1920, 'maxWidth' => 1920, 'minHeight' => 1200, 'fontSize' => 150, 'fontWeight' => 900, 'radius' => 200, 'borderWidth' => 20, 'zIndex' => 9999] as $key => $max) {
            if (array_key_exists($key, $style)) {
                $out[$key] = max($key === 'margin' || $key === 'zIndex' ? -$max : 0, min($max, (int) $style[$key]));
            }
        }
        foreach (['background', 'color', 'borderColor'] as $key) {
            if (array_key_exists($key, $style)) {
                $out[$key] = $this->color((string) $style[$key], '');
            }
        }
        if (array_key_exists('shadow', $style)) {
            $out['shadow'] = sanitize_text_field((string) $style['shadow']);
        }
        return $out;
    }

    private function sanitize_design(array $design): array
    {
        $out = [];
        $out['cssId'] = sanitize_html_class((string) ($design['cssId'] ?? ''));
        $classes = preg_split('/\s+/', (string) ($design['cssClasses'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out['cssClasses'] = implode(' ', array_slice(array_filter(array_map('sanitize_html_class', $classes)), 0, 20));
        foreach (['entranceAnimation', 'hoverAnimation', 'maskShape', 'maskSize', 'maskPosition', 'position', 'globalStyle', 'colorToken', 'backgroundToken', 'typographyToken'] as $key) {
            $out[$key] = sanitize_key((string) ($design[$key] ?? ''));
        }
        foreach (['animationDuration', 'animationDelay', 'zIndex', 'rotate', 'skewX', 'skewY'] as $key) {
            $out[$key] = max(-9999, min(9999, (int) ($design[$key] ?? 0)));
        }
        $out['scale'] = max(0, min(3, (float) ($design['scale'] ?? 1)));
        foreach (['marginBox', 'paddingBox', 'widthUnit', 'minHeightUnit', 'translateX', 'translateY', 'typography', 'backgroundControl', 'borderControl'] as $key) {
            $out[$key] = $this->sanitize_prop_value($design[$key] ?? [], $key, 0);
        }
        $out['attributes'] = [];
        foreach (array_slice((array) ($design['attributes'] ?? []), 0, 30) as $attribute) {
            if (!is_array($attribute)) continue;
            $key = strtolower(sanitize_key((string) ($attribute['key'] ?? '')));
            if (!preg_match('/^(data|aria)-[a-z0-9_-]+$/', $key) && !in_array($key, ['title', 'role', 'tabindex'], true)) continue;
            $out['attributes'][] = ['key' => $key, 'value' => sanitize_text_field((string) ($attribute['value'] ?? ''))];
        }
        $css = sanitize_textarea_field((string) ($design['customCss'] ?? ''));
        $css = preg_replace('/@(?:import|charset|namespace)|expression\s*\(|javascript\s*:|<\/?style/i', '', $css) ?: '';
        $out['customCss'] = substr($css, 0, 10000);
        return $out;
    }

    private function sanitize_design_system(array $system): array
    {
        $defaults = $this->default_design_system();
        $out = ['colors' => [], 'typography' => [], 'spacing' => [], 'radii' => [], 'shadows' => [], 'styles' => []];
        foreach (array_keys($out) as $group) {
            $items = is_array($system[$group] ?? null) ? $system[$group] : $defaults[$group];
            foreach (array_slice($items, 0, 100) as $item) {
                if (!is_array($item)) continue;
                $clean = [];
                $key_map = ['lineheight' => 'lineHeight', 'colortoken' => 'colorToken', 'backgroundtoken' => 'backgroundToken', 'paddingtoken' => 'paddingToken', 'radiustoken' => 'radiusToken', 'shadowtoken' => 'shadowToken', 'classname' => 'className'];
                foreach ($item as $key => $value) {
                    $key = sanitize_key((string) $key);
                    $key = $key_map[$key] ?? $key;
                    if ($key === 'value' && $group === 'colors') $clean[$key] = $this->color((string) $value, '#000000');
                    elseif (in_array($key, ['size', 'weight', 'lineHeight', 'value'], true) && is_numeric($value)) $clean[$key] = (float) $value;
                    else $clean[$key] = sanitize_text_field((string) $value);
                }
                $clean['id'] = sanitize_key((string) ($clean['id'] ?? ''));
                if ($clean['id'] !== '') $out[$group][] = $clean;
            }
        }
        $breakpoints = (array) ($system['breakpoints'] ?? $defaults['breakpoints']);
        $out['breakpoints'] = ['tablet' => max(768, min(1440, (int) ($breakpoints['tablet'] ?? 1024))), 'mobile' => max(320, min(1023, (int) ($breakpoints['mobile'] ?? 767)))];
        $css = sanitize_textarea_field((string) ($system['customCss'] ?? ''));
        $out['customCss'] = substr((string) (preg_replace('/@(?:import|charset|namespace)|expression\s*\(|javascript\s*:|<\/?style/i', '', $css) ?: ''), 0, 20000);
        return $out;
    }

    private function default_design_system(): array
    {
        return [
            'colors' => [['id' => 'primary', 'name' => 'اصلی', 'value' => '#7c3aed'], ['id' => 'text', 'name' => 'متن', 'value' => '#17202a'], ['id' => 'surface', 'name' => 'سطح', 'value' => '#ffffff']],
            'typography' => [['id' => 'body', 'name' => 'متن اصلی', 'family' => 'Vazirmatn', 'size' => 16, 'weight' => 400, 'lineHeight' => 1.8], ['id' => 'heading', 'name' => 'عنوان', 'family' => 'Vazirmatn', 'size' => 36, 'weight' => 800, 'lineHeight' => 1.3]],
            'spacing' => [['id' => 'sm', 'name' => 'کم', 'value' => 8], ['id' => 'md', 'name' => 'متوسط', 'value' => 16], ['id' => 'lg', 'name' => 'زیاد', 'value' => 32]],
            'radii' => [['id' => 'sm', 'name' => 'کم', 'value' => 6], ['id' => 'md', 'name' => 'متوسط', 'value' => 12]],
            'shadows' => [['id' => 'card', 'name' => 'کارت', 'value' => '0 10px 30px #0f172a1a']],
            'styles' => [['id' => 'card', 'name' => 'کارت عمومی', 'colorToken' => 'text', 'backgroundToken' => 'surface', 'paddingToken' => 'md', 'radiusToken' => 'md', 'shadowToken' => 'card', 'className' => 'woact-global-card']],
            'breakpoints' => ['tablet' => 1024, 'mobile' => 767], 'customCss' => '',
        ];
    }

    private function sanitize_prop_value($value, string $key, int $depth)
    {
        if ($depth > 5) return null;
        if (is_array($value)) {
            $out = [];
            foreach (array_slice($value, 0, 100, true) as $child_key => $child_value) {
                $clean_key = is_int($child_key) ? $child_key : $this->safe_prop_key((string) $child_key);
                if ($clean_key === '') continue;
                $out[$clean_key] = $this->sanitize_prop_value($child_value, (string) $clean_key, $depth + 1);
            }
            return $out;
        }
        if (is_bool($value) || is_numeric($value) || $value === null) return $value;
        if (stripos($key, 'url') !== false || in_array($key, ['image', 'poster', 'src'], true)) {
            return esc_url_raw((string) $value);
        }
        return sanitize_textarea_field((string) $value);
    }

    private function sanitize_html_widget(string $html): string
    {
        $styles = [];
        $html = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function (array $match) use (&$styles): string {
            $styles[] = $this->sanitize_widget_css((string) ($match[1] ?? ''));
            return '';
        }, $html) ?? '';
        $allowed = wp_kses_allowed_html('post');
        $allowed['svg'] = ['class' => true, 'xmlns' => true, 'viewbox' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'role' => true, 'aria-hidden' => true, 'focusable' => true];
        $allowed['g'] = ['fill' => true, 'stroke' => true, 'transform' => true, 'opacity' => true, 'class' => true];
        $allowed['path'] = ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'transform' => true, 'opacity' => true, 'class' => true];
        $allowed['circle'] = ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true];
        $allowed['rect'] = ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true];
        foreach (['line', 'polyline', 'polygon'] as $tag) $allowed[$tag] = ['x1' => true, 'x2' => true, 'y1' => true, 'y2' => true, 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true];
        $clean_html = wp_kses($html, $allowed);
        $clean_css = implode("\n", array_filter($styles));
        return ($clean_css !== '' ? '<style>' . $clean_css . '</style>' : '') . $clean_html;
    }

    private function sanitize_widget_css(string $css): string
    {
        $css = sanitize_textarea_field($css);
        $css = preg_replace('/@(?:import|charset|namespace)|expression\s*\(|javascript\s*:|vbscript\s*:|behavior\s*:|-moz-binding\s*:|<\/?style/i', '', $css) ?: '';
        $css = preg_replace_callback('/url\(\s*(["\']?)(.*?)\1\s*\)/i', static function (array $match): string {
            $url = esc_url_raw(trim((string) ($match[2] ?? '')), ['http', 'https']);
            return $url !== '' ? 'url("' . str_replace(['"', "'", '(', ')'], '', $url) . '")' : 'none';
        }, $css) ?? '';
        return substr($css, 0, 100000);
    }

    private function canonical_prop_key(string $type, string $key): string
    {
        $safe = $this->safe_prop_key($key);
        if ($safe === '') return '';
        if (!$this->prop_keys) {
            $components = [
                ['type' => 'section', 'defaults' => ['tag' => 'section']],
                ['type' => 'column', 'defaults' => ['basis' => 6]],
                ['type' => 'heading', 'defaults' => ['text' => '', 'level' => 'h2']],
                ['type' => 'text', 'defaults' => ['text' => '']],
                ['type' => 'button', 'defaults' => ['label' => '', 'url' => '', 'variant' => 'primary']],
                ['type' => 'image', 'defaults' => ['url' => '', 'alt' => '', 'caption' => '']],
                ['type' => 'icon', 'defaults' => ['icon' => '']],
                ['type' => 'list', 'defaults' => ['items' => '']],
                ['type' => 'quote', 'defaults' => ['text' => '', 'author' => '']],
                ['type' => 'spacer', 'defaults' => ['height' => 40]],
                ['type' => 'html', 'defaults' => ['html' => '']],
            ];
            if ($this->widgets) $components = array_merge($components, $this->widgets->definitions());
            foreach ($components as $component) {
                if (!is_array($component)) continue;
                $component_type = (string) ($component['type'] ?? '');
                $known = array_keys((array) ($component['defaults'] ?? []));
                foreach ((array) ($component['fields'] ?? []) as $field) {
                    if (is_array($field) && isset($field['id'])) $known[] = (string) $field['id'];
                }
                foreach (array_unique($known) as $candidate) $this->prop_keys[$component_type][strtolower($candidate)] = $candidate;
            }
        }
        return (string) ($this->prop_keys[$type][strtolower($safe)] ?? $safe);
    }

    private function safe_prop_key(string $key): string
    {
        $key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key) ?: '';
        return substr($key, 0, 80);
    }

    private function blank_document(): array
    {
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'settings' => ['contentWidth' => 1180, 'background' => '#ffffff', 'className' => '', 'hideTitle' => true, 'designSystem' => $this->default_design_system()],
            'nodes' => [[
                'id' => $this->new_id('section'), 'type' => 'section', 'props' => ['tag' => 'section'],
                'style' => ['display' => 'flex', 'direction' => 'column', 'gap' => 16, 'padding' => 48, 'margin' => 0, 'background' => '#ffffff', 'color' => '#17202a', 'width' => 0, 'maxWidth' => 1180, 'minHeight' => 260, 'align' => 'center', 'justify' => 'center', 'fontSize' => 0, 'fontWeight' => 0, 'radius' => 0, 'borderWidth' => 0, 'borderColor' => '', 'shadow' => '', 'zIndex' => 0],
                'responsive' => ['tablet' => [], 'mobile' => ['padding' => 24]],
                'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true], 'dynamic' => '', 'locked' => false,
                'children' => [[
                    'id' => $this->new_id('heading'), 'type' => 'heading', 'props' => ['text' => 'صفحه React جدید', 'level' => 'h1'],
                    'style' => ['display' => 'block', 'direction' => 'column', 'gap' => 0, 'padding' => 0, 'margin' => 0, 'background' => '', 'color' => '#0f172a', 'width' => 0, 'maxWidth' => 0, 'minHeight' => 0, 'align' => 'start', 'justify' => 'start', 'fontSize' => 42, 'fontWeight' => 800, 'radius' => 0, 'borderWidth' => 0, 'borderColor' => '', 'shadow' => '', 'zIndex' => 0],
                    'responsive' => ['tablet' => [], 'mobile' => ['fontSize' => 30]], 'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true], 'dynamic' => '', 'locked' => false, 'children' => [],
                ]],
            ]],
        ];
    }

    private function revision_token(int $post_id, array $document): string
    {
        return substr(hash('sha256', $post_id . ':' . (string) wp_json_encode($document)), 0, 20);
    }

    private function color(string $value, string $fallback): string
    {
        if ($value === '') return $fallback;
        $clean = sanitize_hex_color($value);
        return $clean ?: $fallback;
    }

    private function clean_id(string $id): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?: '';
        return $id ?: $this->new_id('node');
    }

    private function stable_node_id(string $id, string $type, string $path): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?: '';
        if ($clean !== '') return $clean;
        return sanitize_key($type ?: 'node') . '-' . substr(hash('sha256', $path . ':' . $type), 0, 12);
    }

    private function is_allowed_type(string $type): bool
    {
        return in_array($type, self::NODE_TYPES, true) || ($this->widgets && $this->widgets->has($type));
    }

    private function new_id(string $prefix): string
    {
        return $prefix . '_' . substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
    }
}
