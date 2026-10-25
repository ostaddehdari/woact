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

    public function __construct(?Woact_Widget_Registry $widgets = null)
    {
        $this->widgets = $widgets;
    }

    public function get_manifest(): array
    {
        $field = static fn(string $id, string $label, string $type = 'text', $default = ''): array => compact('id', 'label', 'type', 'default');
        $manifest = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'categories' => [
                ['id' => 'layout', 'label' => 'چیدمان'],
                ['id' => 'basic', 'label' => 'پایه'],
                ['id' => 'media', 'label' => 'رسانه'],
            ],
            'components' => [
                ['type' => 'section', 'label' => 'بخش', 'icon' => 'align-wide', 'category' => 'layout', 'container' => true, 'defaults' => ['tag' => 'section'], 'fields' => [$field('tag', 'تگ HTML', 'select', 'section')]],
                ['type' => 'container', 'label' => 'کانتینر', 'icon' => 'editor-table', 'category' => 'layout', 'container' => true, 'defaults' => [], 'fields' => []],
                ['type' => 'columns', 'label' => 'ستون‌ها', 'icon' => 'columns', 'category' => 'layout', 'container' => true, 'defaults' => [], 'fields' => []],
                ['type' => 'column', 'label' => 'ستون', 'icon' => 'excerpt-view', 'category' => 'layout', 'container' => true, 'defaults' => ['basis' => 6], 'fields' => [$field('basis', 'عرض از ۱۲', 'number', 6)]],
                ['type' => 'heading', 'label' => 'عنوان', 'icon' => 'heading', 'category' => 'basic', 'defaults' => ['text' => 'عنوان جدید', 'level' => 'h2'], 'fields' => [$field('text', 'متن', 'textarea'), $field('level', 'سطح عنوان', 'select', 'h2')]],
                ['type' => 'text', 'label' => 'متن', 'icon' => 'editor-paragraph', 'category' => 'basic', 'defaults' => ['text' => 'متن خود را اینجا بنویسید.'], 'fields' => [$field('text', 'متن', 'textarea')]],
                ['type' => 'button', 'label' => 'دکمه', 'icon' => 'button', 'category' => 'basic', 'defaults' => ['label' => 'بیشتر بدانید', 'url' => '#', 'variant' => 'primary'], 'fields' => [$field('label', 'عنوان'), $field('url', 'پیوند', 'url'), $field('variant', 'نوع', 'select', 'primary')]],
                ['type' => 'image', 'label' => 'تصویر', 'icon' => 'format-image', 'category' => 'media', 'defaults' => ['url' => '', 'alt' => '', 'caption' => ''], 'fields' => [$field('url', 'نشانی تصویر', 'url'), $field('alt', 'متن جایگزین'), $field('caption', 'زیرنویس')]],
                ['type' => 'icon', 'label' => 'آیکون', 'icon' => 'star-filled', 'category' => 'basic', 'defaults' => ['icon' => '★'], 'fields' => [$field('icon', 'آیکون')]],
                ['type' => 'list', 'label' => 'فهرست', 'icon' => 'editor-ul', 'category' => 'basic', 'defaults' => ['items' => "مورد اول\nمورد دوم\nمورد سوم"], 'fields' => [$field('items', 'هر مورد در یک خط', 'textarea')]],
                ['type' => 'quote', 'label' => 'نقل‌قول', 'icon' => 'format-quote', 'category' => 'basic', 'defaults' => ['text' => 'متن نقل‌قول', 'author' => ''], 'fields' => [$field('text', 'متن', 'textarea'), $field('author', 'نویسنده')]],
                ['type' => 'divider', 'label' => 'جداکننده', 'icon' => 'minus', 'category' => 'layout', 'defaults' => [], 'fields' => []],
                ['type' => 'spacer', 'label' => 'فاصله', 'icon' => 'leftright', 'category' => 'layout', 'defaults' => ['height' => 40], 'fields' => [$field('height', 'ارتفاع', 'number', 40)]],
                ['type' => 'html', 'label' => 'HTML امن', 'icon' => 'editor-code', 'category' => 'basic', 'defaults' => ['html' => ''], 'fields' => [$field('html', 'HTML', 'textarea')]],
            ],
            'breakpoints' => ['desktop' => 1200, 'tablet' => 782, 'mobile' => 480],
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
            'viewUrl' => get_permalink($post_id),
        ];
    }

    public function save(int $post_id, array $input, string $expected_revision)
    {
        $current = $this->get($post_id);
        if (!$current) {
            return new WP_Error('woact_page_missing', __('برگه پیدا نشد.', 'woact-core'), ['status' => 404]);
        }
        if ($expected_revision !== '' && !hash_equals($current['revision'], $expected_revision)) {
            return new WP_Error('woact_page_conflict', __('این صفحه در پنجرهٔ دیگری تغییر کرده است.', 'woact-core'), ['status' => 409, 'currentRevision' => $current['revision']]);
        }
        $document = $this->sanitize_document((array) ($input['document'] ?? []));
        update_post_meta($post_id, self::META_DRAFT, $document);
        update_post_meta($post_id, self::META_ENABLED, !empty($input['enabled']) ? '1' : '0');
        if (isset($input['title'])) {
            wp_update_post(['ID' => $post_id, 'post_title' => sanitize_text_field((string) $input['title'])]);
        }
        clean_post_cache($post_id);
        return $this->get($post_id);
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
        $html = '<div class="woact-page-runtime" style="--woact-page-width:' . (int) ($document['settings']['contentWidth'] ?? 1180) . 'px;background:' . esc_attr((string) ($document['settings']['background'] ?? '#ffffff')) . '">';
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
        $class = 'woact-page-node woact-page-node--' . esc_attr($type);
        $dynamic = $this->dynamic_value((string) ($node['dynamic'] ?? ''), $data);
        if (in_array($type, self::CONTAINERS, true)) {
            $tag = $type === 'section' && in_array(($props['tag'] ?? ''), ['section', 'header', 'footer', 'main', 'article', 'aside'], true) ? $props['tag'] : 'div';
            $inner = '';
            foreach ((array) ($node['children'] ?? []) as $child) $inner .= $this->render_node((array) $child, $data);
            return '<' . $tag . ' class="' . $class . '" style="' . $css . '">' . $inner . '</' . $tag . '>';
        }
        if ($type === 'heading') { $tag = preg_match('/^h[1-6]$/', (string) ($props['level'] ?? '')) ? $props['level'] : 'h2'; return '<' . $tag . ' class="' . $class . '" style="' . $css . '">' . esc_html((string) ($dynamic ?? $props['text'] ?? '')) . '</' . $tag . '>'; }
        if ($type === 'text') return '<p class="' . $class . '" style="' . $css . '">' . esc_html((string) ($dynamic ?? $props['text'] ?? '')) . '</p>';
        if ($type === 'button') return '<a class="' . $class . ' is-' . esc_attr((string) ($props['variant'] ?? 'primary')) . '" style="' . $css . '" href="' . esc_url((string) ($props['url'] ?? '#')) . '">' . esc_html((string) ($props['label'] ?? '')) . '</a>';
        if ($type === 'image') { $src = (string) ($dynamic ?? $props['url'] ?? ''); return '<figure class="' . $class . '" style="' . $css . '">' . ($src ? '<img src="' . esc_url($src) . '" alt="' . esc_attr((string) ($props['alt'] ?? '')) . '">' : '') . '</figure>'; }
        if ($type === 'html') return '<div class="' . $class . '" style="' . $css . '">' . wp_kses_post((string) ($props['html'] ?? '')) . '</div>';
        if ($type === 'divider') return '<hr class="' . $class . '" style="' . $css . '">';
        if ($type === 'spacer') return '<div class="' . $class . '" style="' . $css . 'height:' . (int) ($props['height'] ?? 40) . 'px"></div>';
        $text = (string) ($props['icon'] ?? $props['text'] ?? '');
        return '<div class="' . $class . '" style="' . $css . '">' . esc_html($text) . '</div>';
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
        foreach (array_slice((array) ($document['nodes'] ?? []), 0, 100) as $node) {
            $clean = $this->sanitize_node($node, 0);
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
            ],
            'nodes' => $nodes ?: $this->blank_document()['nodes'],
        ];
    }

    private function sanitize_node($node, int $depth): ?array
    {
        if (!is_array($node) || $depth > 8 || !$this->is_allowed_type((string) ($node['type'] ?? ''))) {
            return null;
        }
        $type = (string) $node['type'];
        $props = [];
        foreach ((array) ($node['props'] ?? []) as $key => $value) {
            $key = sanitize_key((string) $key);
            if ($key === 'html' && $type === 'html') {
                $props[$key] = wp_kses_post((string) $value);
            } elseif (strpos($key, 'url') !== false) {
                $props[$key] = esc_url_raw((string) $value);
            } elseif (is_bool($value) || is_numeric($value)) {
                $props[$key] = $value;
            } else {
                $props[$key] = sanitize_textarea_field((string) $value);
            }
        }
        $children = [];
        if (in_array($type, self::CONTAINERS, true)) {
            foreach (array_slice((array) ($node['children'] ?? []), 0, 50) as $child) {
                $clean = $this->sanitize_node($child, $depth + 1);
                if ($clean) {
                    $children[] = $clean;
                }
            }
        }
        return [
            'id' => $this->clean_id((string) ($node['id'] ?? 'node')),
            'type' => $type,
            'props' => $props,
            'style' => $this->sanitize_style((array) ($node['style'] ?? [])),
            'responsive' => [
                'tablet' => $this->sanitize_style((array) ($node['responsive']['tablet'] ?? []), true),
                'mobile' => $this->sanitize_style((array) ($node['responsive']['mobile'] ?? []), true),
            ],
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

    private function blank_document(): array
    {
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'settings' => ['contentWidth' => 1180, 'background' => '#ffffff', 'className' => '', 'hideTitle' => true],
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

    private function is_allowed_type(string $type): bool
    {
        return in_array($type, self::NODE_TYPES, true) || ($this->widgets && $this->widgets->has($type));
    }

    private function new_id(string $prefix): string
    {
        return $prefix . '_' . substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
    }
}
