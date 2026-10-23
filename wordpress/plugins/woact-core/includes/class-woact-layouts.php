<?php
declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WOACT_TESTING')) {
    exit;
}

/**
 * Versioned storage, validation and runtime resolution for header/footer documents.
 */
final class Woact_Layouts
{
    public const OPTION = 'woact_layout_templates';
    public const DB_VERSION_OPTION = 'woact_layouts_db_version';
    public const SCHEMA_VERSION = 1;

    private const TYPES = ['header', 'footer'];
    private const ELEMENT_TYPES = [
        'logo', 'menu', 'search', 'button', 'text', 'html', 'icon', 'social', 'language',
        'breadcrumb', 'spacer', 'divider', 'account', 'cart', 'wishlist', 'compare',
        'categories', 'copyright', 'newsletter', 'contact', 'trust_badges', 'payments', 'widget',
    ];

    public function install_or_upgrade(): void
    {
        $templates = get_option(self::OPTION, null);
        if (!is_array($templates) || !$templates) {
            $templates = $this->default_templates();
            update_option(self::OPTION, $templates, false);
        } else {
            $templates = array_map([$this, 'sanitize_template'], $templates);
            update_option(self::OPTION, array_values($templates), false);
        }
        update_option(self::DB_VERSION_OPTION, self::SCHEMA_VERSION, false);
    }

    public function get_templates(): array
    {
        $templates = get_option(self::OPTION, []);
        if (!is_array($templates)) {
            return [];
        }
        return array_values(array_map([$this, 'sanitize_template'], $templates));
    }

    public function get_template(string $id): ?array
    {
        foreach ($this->get_templates() as $template) {
            if ($template['id'] === $id) {
                return $template;
            }
        }
        return null;
    }

    public function get_revision_token(array $template): string
    {
        $snapshot = [$template['title'], $template['conditions'], $template['draft'], $template['updatedAt']];
        return substr(hash('sha256', (string) wp_json_encode($snapshot)), 0, 20);
    }

    public function create(string $type, string $title, int $user_id)
    {
        $type = in_array($type, self::TYPES, true) ? $type : '';
        if ($type === '') {
            return new WP_Error('woact_layout_type', __('نوع قالب معتبر نیست.', 'woact-core'), ['status' => 422]);
        }
        $templates = $this->get_templates();
        $document = $this->blank_document($type);
        $template = [
            'id' => 'layout_' . str_replace('-', '', wp_generate_uuid4()),
            'type' => $type,
            'title' => sanitize_text_field($title ?: ($type === 'header' ? 'هدر جدید' : 'فوتر جدید')),
            'active' => false,
            'priority' => 10,
            'conditions' => [['scope' => 'entire_site', 'value' => '', 'exclude' => false]],
            'draft' => $document,
            'published' => null,
            'revisions' => [],
            'createdBy' => $user_id,
            'updatedAt' => time(),
        ];
        $templates[] = $template;
        $this->persist($templates);
        return $this->with_revision($template);
    }

    public function save_draft(string $id, array $input, string $expected_revision, int $user_id)
    {
        $templates = $this->get_templates();
        foreach ($templates as $index => $template) {
            if ($template['id'] !== $id) {
                continue;
            }
            $current_revision = $this->get_revision_token($template);
            if ($expected_revision !== '' && !hash_equals($current_revision, $expected_revision)) {
                return new WP_Error(
                    'woact_layout_conflict',
                    __('این قالب در پنجرهٔ دیگری تغییر کرده است.', 'woact-core'),
                    ['status' => 409, 'currentRevision' => $current_revision]
                );
            }
            $template['title'] = sanitize_text_field((string) ($input['title'] ?? $template['title']));
            $template['active'] = !empty($input['active']);
            $template['priority'] = max(0, min(1000, (int) ($input['priority'] ?? $template['priority'])));
            $template['conditions'] = $this->sanitize_conditions((array) ($input['conditions'] ?? $template['conditions']));
            $template['draft'] = $this->sanitize_document((array) ($input['document'] ?? []), $template['type']);
            $template['updatedAt'] = time();
            $template['updatedBy'] = $user_id;
            $templates[$index] = $template;
            $this->persist($templates);
            return $this->with_revision($template);
        }
        return new WP_Error('woact_layout_missing', __('قالب پیدا نشد.', 'woact-core'), ['status' => 404]);
    }

    public function publish(string $id, int $user_id)
    {
        $templates = $this->get_templates();
        foreach ($templates as $index => $template) {
            if ($template['id'] !== $id) {
                continue;
            }
            if (is_array($template['published'])) {
                array_unshift($template['revisions'], [
                    'id' => wp_generate_uuid4(),
                    'createdAt' => time(),
                    'userId' => $user_id,
                    'document' => $template['published'],
                    'conditions' => $template['conditions'],
                ]);
                $template['revisions'] = array_slice($template['revisions'], 0, 30);
            }
            $template['published'] = $template['draft'];
            $template['active'] = true;
            $template['publishedAt'] = time();
            $template['updatedAt'] = time();
            $template['updatedBy'] = $user_id;
            $templates[$index] = $template;
            $this->persist($templates);
            return $this->with_revision($template);
        }
        return new WP_Error('woact_layout_missing', __('قالب پیدا نشد.', 'woact-core'), ['status' => 404]);
    }

    public function duplicate(string $id, int $user_id)
    {
        $templates = $this->get_templates();
        foreach ($templates as $template) {
            if ($template['id'] !== $id) {
                continue;
            }
            $copy = $template;
            $copy['id'] = 'layout_' . str_replace('-', '', wp_generate_uuid4());
            $copy['title'] = sprintf(__('%s — کپی', 'woact-core'), $template['title']);
            $copy['active'] = false;
            $copy['published'] = null;
            $copy['revisions'] = [];
            $copy['createdBy'] = $user_id;
            $copy['updatedAt'] = time();
            $templates[] = $copy;
            $this->persist($templates);
            return $this->with_revision($copy);
        }
        return new WP_Error('woact_layout_missing', __('قالب پیدا نشد.', 'woact-core'), ['status' => 404]);
    }

    public function delete(string $id): bool
    {
        $templates = $this->get_templates();
        $next = array_values(array_filter($templates, static fn(array $template): bool => $template['id'] !== $id));
        if (count($next) === count($templates)) {
            return false;
        }
        $this->persist($next);
        return true;
    }

    public function restore(string $id, string $revision_id, int $user_id)
    {
        $templates = $this->get_templates();
        foreach ($templates as $index => $template) {
            if ($template['id'] !== $id) {
                continue;
            }
            foreach ($template['revisions'] as $revision) {
                if (($revision['id'] ?? '') !== $revision_id || !is_array($revision['document'] ?? null)) {
                    continue;
                }
                if (is_array($template['published'])) {
                    array_unshift($template['revisions'], [
                        'id' => wp_generate_uuid4(),
                        'createdAt' => time(),
                        'userId' => $user_id,
                        'document' => $template['published'],
                        'conditions' => $template['conditions'],
                    ]);
                }
                $template['published'] = $this->sanitize_document($revision['document'], $template['type']);
                $template['draft'] = $template['published'];
                $template['conditions'] = $this->sanitize_conditions((array) ($revision['conditions'] ?? $template['conditions']));
                $template['updatedAt'] = time();
                $template['publishedAt'] = time();
                $templates[$index] = $template;
                $this->persist($templates);
                return $this->with_revision($template);
            }
        }
        return new WP_Error('woact_layout_revision_missing', __('نسخهٔ انتخاب‌شده پیدا نشد.', 'woact-core'), ['status' => 404]);
    }

    public function import(array $input, int $user_id)
    {
        if ((int) ($input['schemaVersion'] ?? 0) > self::SCHEMA_VERSION || !is_array($input['template'] ?? null)) {
            return new WP_Error('woact_layout_import', __('فایل قالب معتبر نیست.', 'woact-core'), ['status' => 422]);
        }
        $source = $input['template'];
        $type = in_array(($source['type'] ?? ''), self::TYPES, true) ? $source['type'] : '';
        if ($type === '') {
            return new WP_Error('woact_layout_import_type', __('نوع قالب واردشده معتبر نیست.', 'woact-core'), ['status' => 422]);
        }
        $template = [
            'id' => 'layout_' . str_replace('-', '', wp_generate_uuid4()),
            'type' => $type,
            'title' => sanitize_text_field((string) ($source['title'] ?? 'قالب واردشده')),
            'active' => false,
            'priority' => (int) ($source['priority'] ?? 10),
            'conditions' => $this->sanitize_conditions((array) ($source['conditions'] ?? [])),
            'draft' => $this->sanitize_document((array) ($source['document'] ?? $source['draft'] ?? []), $type),
            'published' => null,
            'revisions' => [],
            'createdBy' => $user_id,
            'updatedAt' => time(),
        ];
        $templates = $this->get_templates();
        $templates[] = $template;
        $this->persist($templates);
        return $this->with_revision($template);
    }

    public function resolve(string $type, ?array $context = null): ?array
    {
        if (!in_array($type, self::TYPES, true)) {
            return null;
        }
        $context = $context ?? $this->request_context();
        $matches = array_filter($this->get_templates(), function (array $template) use ($type, $context): bool {
            return $template['type'] === $type
                && $template['active']
                && is_array($template['published'])
                && $this->conditions_match($template['conditions'], $context);
        });
        usort($matches, static function (array $a, array $b): int {
            $priority = $b['priority'] <=> $a['priority'];
            return $priority !== 0 ? $priority : ($b['updatedAt'] <=> $a['updatedAt']);
        });
        return $matches ? reset($matches) : null;
    }

    public function get_runtime_payload(string $type, ?array $template = null): ?array
    {
        $template = $template ?? $this->resolve($type);
        if (!$template) {
            return null;
        }
        $document = is_array($template['published']) ? $template['published'] : $template['draft'];
        return [
            'id' => $template['id'],
            'type' => $type,
            'document' => $document,
            'data' => $this->runtime_data(),
        ];
    }

    public function get_manifest(): array
    {
        $field = static fn(string $id, string $label, string $type = 'text', $default = ''): array => compact('id', 'label', 'type', 'default');
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'elements' => [
                ['type' => 'logo', 'label' => 'لوگو', 'icon' => 'format-image', 'category' => 'general', 'defaults' => ['text' => '', 'width' => 150], 'fields' => [$field('text', 'متن جایگزین'), $field('width', 'عرض', 'number', 150)]],
                ['type' => 'menu', 'label' => 'منو', 'icon' => 'menu', 'category' => 'general', 'defaults' => ['location' => 'primary', 'mobileDrawer' => true], 'fields' => [$field('location', 'جایگاه', 'select', 'primary'), $field('mobileDrawer', 'منوی کشویی موبایل', 'switch', true)]],
                ['type' => 'search', 'label' => 'جست‌وجو', 'icon' => 'search', 'category' => 'general', 'defaults' => ['placeholder' => 'جست‌وجو…'], 'fields' => [$field('placeholder', 'متن راهنما')]],
                ['type' => 'button', 'label' => 'دکمه', 'icon' => 'button', 'category' => 'general', 'defaults' => ['label' => 'دکمه', 'url' => '#', 'variant' => 'primary'], 'fields' => [$field('label', 'عنوان'), $field('url', 'پیوند', 'url'), $field('variant', 'نوع', 'select', 'primary')]],
                ['type' => 'text', 'label' => 'متن', 'icon' => 'editor-textcolor', 'category' => 'general', 'defaults' => ['content' => 'متن'], 'fields' => [$field('content', 'متن', 'textarea')]],
                ['type' => 'html', 'label' => 'HTML امن', 'icon' => 'editor-code', 'category' => 'general', 'defaults' => ['content' => ''], 'fields' => [$field('content', 'HTML', 'textarea')]],
                ['type' => 'icon', 'label' => 'آیکون', 'icon' => 'star-filled', 'category' => 'general', 'defaults' => ['icon' => '★', 'url' => ''], 'fields' => [$field('icon', 'آیکون'), $field('url', 'پیوند', 'url')]],
                ['type' => 'social', 'label' => 'شبکه اجتماعی', 'icon' => 'share', 'category' => 'general', 'defaults' => ['items' => 'Telegram|https://t.me/\nInstagram|https://instagram.com/'], 'fields' => [$field('items', 'هر خط: عنوان|پیوند', 'textarea')]],
                ['type' => 'language', 'label' => 'زبان', 'icon' => 'translation', 'category' => 'general', 'defaults' => ['label' => 'زبان'], 'fields' => [$field('label', 'عنوان')]],
                ['type' => 'breadcrumb', 'label' => 'مسیر راهنما', 'icon' => 'arrow-left-alt2', 'category' => 'general', 'defaults' => [], 'fields' => []],
                ['type' => 'spacer', 'label' => 'فاصله', 'icon' => 'leftright', 'category' => 'layout', 'defaults' => ['size' => 24], 'fields' => [$field('size', 'اندازه', 'number', 24)]],
                ['type' => 'divider', 'label' => 'جداکننده', 'icon' => 'minus', 'category' => 'layout', 'defaults' => ['color' => '#e2e8f0'], 'fields' => [$field('color', 'رنگ', 'color', '#e2e8f0')]],
                ['type' => 'account', 'label' => 'حساب کاربری', 'icon' => 'admin-users', 'category' => 'commerce', 'defaults' => ['label' => 'حساب من'], 'fields' => [$field('label', 'عنوان')]],
                ['type' => 'cart', 'label' => 'سبد خرید', 'icon' => 'cart', 'category' => 'commerce', 'defaults' => ['label' => 'سبد خرید', 'showCount' => true], 'fields' => [$field('label', 'عنوان'), $field('showCount', 'نمایش تعداد', 'switch', true)]],
                ['type' => 'wishlist', 'label' => 'علاقه‌مندی', 'icon' => 'heart', 'category' => 'commerce', 'defaults' => ['label' => 'علاقه‌مندی'], 'fields' => [$field('label', 'عنوان')]],
                ['type' => 'compare', 'label' => 'مقایسه', 'icon' => 'image-flip-horizontal', 'category' => 'commerce', 'defaults' => ['label' => 'مقایسه'], 'fields' => [$field('label', 'عنوان')]],
                ['type' => 'categories', 'label' => 'دسته‌های محصولات', 'icon' => 'category', 'category' => 'commerce', 'defaults' => ['label' => 'دسته‌بندی‌ها'], 'fields' => [$field('label', 'عنوان')]],
                ['type' => 'copyright', 'label' => 'حق نشر', 'icon' => 'info-outline', 'category' => 'footer', 'defaults' => ['text' => '© {year} {site_name}'], 'fields' => [$field('text', 'متن')]],
                ['type' => 'newsletter', 'label' => 'خبرنامه', 'icon' => 'email', 'category' => 'footer', 'defaults' => ['title' => 'عضویت در خبرنامه', 'placeholder' => 'ایمیل شما'], 'fields' => [$field('title', 'عنوان'), $field('placeholder', 'متن فیلد')]],
                ['type' => 'contact', 'label' => 'اطلاعات تماس', 'icon' => 'phone', 'category' => 'footer', 'defaults' => ['text' => 'تلفن و نشانی'], 'fields' => [$field('text', 'اطلاعات', 'textarea')]],
                ['type' => 'trust_badges', 'label' => 'نماد اعتماد', 'icon' => 'shield', 'category' => 'footer', 'defaults' => ['text' => 'نمادهای اعتماد'], 'fields' => [$field('text', 'عنوان')]],
                ['type' => 'payments', 'label' => 'روش‌های پرداخت', 'icon' => 'money-alt', 'category' => 'footer', 'defaults' => ['text' => 'پرداخت امن'], 'fields' => [$field('text', 'عنوان')]],
                ['type' => 'widget', 'label' => 'ناحیه ابزارک', 'icon' => 'screenoptions', 'category' => 'footer', 'defaults' => ['sidebar' => 'footer-1'], 'fields' => [$field('sidebar', 'شناسه ناحیه')]],
            ],
            'categories' => [
                ['id' => 'general', 'label' => 'عمومی'],
                ['id' => 'commerce', 'label' => 'فروشگاه و کاربر'],
                ['id' => 'footer', 'label' => 'فوتر'],
                ['id' => 'layout', 'label' => 'چیدمان'],
            ],
            'breakpoints' => ['desktop' => 1200, 'tablet' => 782, 'mobile' => 480],
        ];
    }

    public function sanitize_document(array $document, string $type): array
    {
        $type = in_array($type, self::TYPES, true) ? $type : 'header';
        $settings = is_array($document['settings'] ?? null) ? $document['settings'] : [];
        $rows = [];
        foreach (array_slice((array) ($document['rows'] ?? []), 0, 12) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $columns = [];
            foreach (array_slice((array) ($row['columns'] ?? []), 0, 6) as $column) {
                if (!is_array($column)) {
                    continue;
                }
                $elements = [];
                foreach (array_slice((array) ($column['elements'] ?? []), 0, 30) as $element) {
                    if (!is_array($element) || !in_array(($element['type'] ?? ''), self::ELEMENT_TYPES, true)) {
                        continue;
                    }
                    $elements[] = $this->sanitize_element($element);
                }
                $columns[] = [
                    'id' => $this->clean_id((string) ($column['id'] ?? 'column')),
                    'width' => max(1, min(12, (int) ($column['width'] ?? 12))),
                    'align' => in_array(($column['align'] ?? ''), ['start', 'center', 'end', 'between'], true) ? $column['align'] : 'start',
                    'elements' => $elements,
                ];
            }
            if (!$columns) {
                $columns[] = ['id' => $this->new_id('column'), 'width' => 12, 'align' => 'start', 'elements' => []];
            }
            $row_style = is_array($row['style'] ?? null) ? $row['style'] : [];
            $rows[] = [
                'id' => $this->clean_id((string) ($row['id'] ?? 'row')),
                'columns' => $columns,
                'style' => [
                    'width' => in_array(($row_style['width'] ?? ''), ['contained', 'full'], true) ? $row_style['width'] : 'contained',
                    'minHeight' => max(0, min(500, (int) ($row_style['minHeight'] ?? 64))),
                    'gap' => max(0, min(80, (int) ($row_style['gap'] ?? 20))),
                    'background' => $this->color((string) ($row_style['background'] ?? '#ffffff'), '#ffffff'),
                    'color' => $this->color((string) ($row_style['color'] ?? '#17202a'), '#17202a'),
                    'sticky' => !empty($row_style['sticky']),
                    'overlay' => !empty($row_style['overlay']),
                ],
                'visibility' => $this->sanitize_visibility((array) ($row['visibility'] ?? [])),
            ];
        }
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'type' => $type,
            'settings' => [
                'mobileBreakpoint' => max(320, min(1024, (int) ($settings['mobileBreakpoint'] ?? 782))),
                'sticky' => !empty($settings['sticky']),
                'shrinkOnScroll' => !empty($settings['shrinkOnScroll']),
                'className' => sanitize_html_class((string) ($settings['className'] ?? '')),
            ],
            'rows' => $rows ?: $this->blank_document($type)['rows'],
        ];
    }

    public function render_fallback(array $payload): string
    {
        $document = (array) ($payload['document'] ?? []);
        $data = (array) ($payload['data'] ?? []);
        $type = ($payload['type'] ?? 'header') === 'footer' ? 'footer' : 'header';
        $html = '<' . $type . ' class="woact-layout woact-layout--' . esc_attr($type) . '">';
        foreach ((array) ($document['rows'] ?? []) as $row) {
            $style = sprintf(
                '--woact-row-bg:%s;--woact-row-color:%s;--woact-row-height:%dpx;--woact-row-gap:%dpx',
                esc_attr((string) ($row['style']['background'] ?? '#fff')),
                esc_attr((string) ($row['style']['color'] ?? '#17202a')),
                (int) ($row['style']['minHeight'] ?? 64),
                (int) ($row['style']['gap'] ?? 20)
            );
            $html .= '<div class="woact-layout-row" style="' . $style . '"><div class="woact-layout-row__inner">';
            foreach ((array) ($row['columns'] ?? []) as $column) {
                $html .= '<div class="woact-layout-column" style="--woact-column:' . (int) ($column['width'] ?? 12) . '">';
                foreach ((array) ($column['elements'] ?? []) as $element) {
                    $html .= $this->render_fallback_element($element, $data);
                }
                $html .= '</div>';
            }
            $html .= '</div></div>';
        }
        return $html . '</' . $type . '>';
    }

    private function render_fallback_element(array $element, array $data): string
    {
        $type = (string) ($element['type'] ?? 'text');
        $props = (array) ($element['props'] ?? []);
        $class = 'woact-layout-element woact-layout-element--' . esc_attr($type);
        if ($type === 'logo') {
            $logo = !empty($data['site']['logo']) ? '<img src="' . esc_url($data['site']['logo']) . '" alt="' . esc_attr($data['site']['name'] ?? '') . '">' : esc_html(($props['text'] ?? '') ?: ($data['site']['name'] ?? 'Woact'));
            return '<a class="' . $class . '" href="' . esc_url($data['site']['url'] ?? '/') . '">' . $logo . '</a>';
        }
        if ($type === 'menu') {
            $items = (array) ($data['menus'][$props['location'] ?? 'primary'] ?? []);
            $out = '<nav class="' . $class . '" aria-label="' . esc_attr__('منو', 'woact-core') . '"><ul>';
            foreach ($items as $item) {
                if (empty($item['parentId'])) {
                    $out .= '<li><a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a></li>';
                }
            }
            return $out . '</ul></nav>';
        }
        if ($type === 'button') {
            return '<a class="' . $class . ' woact-layout-button" href="' . esc_url($props['url'] ?? '#') . '">' . esc_html($props['label'] ?? '') . '</a>';
        }
        if ($type === 'search') {
            return '<form class="' . $class . '" role="search" action="' . esc_url($data['site']['url'] ?? '/') . '"><input name="s" type="search" placeholder="' . esc_attr($props['placeholder'] ?? '') . '"><button type="submit">' . esc_html__('جست‌وجو', 'woact-core') . '</button></form>';
        }
        if ($type === 'html') {
            return '<div class="' . $class . '">' . wp_kses_post((string) ($props['content'] ?? '')) . '</div>';
        }
        if ($type === 'cart' || $type === 'account' || $type === 'wishlist' || $type === 'compare') {
            $url = (string) ($data['commerce'][$type . 'Url'] ?? '#');
            $count = $type === 'cart' && !empty($props['showCount']) ? ' <span class="woact-layout-badge">' . (int) ($data['commerce']['cartCount'] ?? 0) . '</span>' : '';
            return '<a class="' . $class . '" href="' . esc_url($url) . '">' . esc_html($props['label'] ?? '') . $count . '</a>';
        }
        if ($type === 'copyright') {
            $text = strtr((string) ($props['text'] ?? ''), ['{year}' => wp_date('Y'), '{site_name}' => (string) ($data['site']['name'] ?? '')]);
            return '<span class="' . $class . '">' . esc_html($text) . '</span>';
        }
        if ($type === 'divider') {
            return '<span class="' . $class . '" aria-hidden="true"></span>';
        }
        if ($type === 'spacer') {
            return '<span class="' . $class . '" style="width:' . (int) ($props['size'] ?? 24) . 'px" aria-hidden="true"></span>';
        }
        $text = (string) ($props['content'] ?? $props['text'] ?? $props['label'] ?? $props['title'] ?? '');
        return '<span class="' . $class . '">' . esc_html($text) . '</span>';
    }

    private function sanitize_template(array $template): array
    {
        $type = in_array(($template['type'] ?? ''), self::TYPES, true) ? $template['type'] : 'header';
        $published = is_array($template['published'] ?? null) ? $this->sanitize_document($template['published'], $type) : null;
        return [
            'id' => $this->clean_id((string) ($template['id'] ?? 'layout')),
            'type' => $type,
            'title' => sanitize_text_field((string) ($template['title'] ?? 'قالب')),
            'active' => !empty($template['active']),
            'priority' => max(0, min(1000, (int) ($template['priority'] ?? 10))),
            'conditions' => $this->sanitize_conditions((array) ($template['conditions'] ?? [])),
            'draft' => $this->sanitize_document((array) ($template['draft'] ?? $published ?? []), $type),
            'published' => $published,
            'revisions' => array_slice(is_array($template['revisions'] ?? null) ? $template['revisions'] : [], 0, 30),
            'createdBy' => (int) ($template['createdBy'] ?? 0),
            'updatedBy' => (int) ($template['updatedBy'] ?? 0),
            'updatedAt' => (int) ($template['updatedAt'] ?? 0),
            'publishedAt' => (int) ($template['publishedAt'] ?? 0),
        ];
    }

    private function sanitize_element(array $element): array
    {
        $props = [];
        foreach ((array) ($element['props'] ?? []) as $key => $value) {
            $key = sanitize_key((string) $key);
            if (is_bool($value)) {
                $props[$key] = $value;
            } elseif (is_numeric($value)) {
                $props[$key] = (float) $value;
            } elseif ($key === 'content' && ($element['type'] ?? '') === 'html') {
                $props[$key] = wp_kses_post((string) $value);
            } elseif (strpos($key, 'url') !== false) {
                $props[$key] = esc_url_raw((string) $value);
            } else {
                $props[$key] = sanitize_textarea_field((string) $value);
            }
        }
        $style = is_array($element['style'] ?? null) ? $element['style'] : [];
        return [
            'id' => $this->clean_id((string) ($element['id'] ?? 'element')),
            'type' => (string) $element['type'],
            'props' => $props,
            'style' => [
                'color' => $this->color((string) ($style['color'] ?? ''), ''),
                'background' => $this->color((string) ($style['background'] ?? ''), ''),
                'fontSize' => max(0, min(96, (int) ($style['fontSize'] ?? 0))),
                'padding' => max(0, min(80, (int) ($style['padding'] ?? 0))),
                'radius' => max(0, min(60, (int) ($style['radius'] ?? 0))),
            ],
            'visibility' => $this->sanitize_visibility((array) ($element['visibility'] ?? [])),
        ];
    }

    private function sanitize_conditions(array $conditions): array
    {
        $allowed = ['entire_site', 'front_page', 'singular', 'archive', 'post_type', 'logged_in', 'logged_out'];
        $clean = [];
        foreach (array_slice($conditions, 0, 20) as $condition) {
            if (!is_array($condition) || !in_array(($condition['scope'] ?? ''), $allowed, true)) {
                continue;
            }
            $clean[] = [
                'scope' => $condition['scope'],
                'value' => sanitize_key((string) ($condition['value'] ?? '')),
                'exclude' => !empty($condition['exclude']),
            ];
        }
        return $clean ?: [['scope' => 'entire_site', 'value' => '', 'exclude' => false]];
    }

    private function conditions_match(array $conditions, array $context): bool
    {
        $included = false;
        foreach ($conditions as $condition) {
            $match = $this->condition_matches($condition, $context);
            if (!empty($condition['exclude']) && $match) {
                return false;
            }
            if (empty($condition['exclude']) && $match) {
                $included = true;
            }
        }
        return $included;
    }

    private function condition_matches(array $condition, array $context): bool
    {
        switch ($condition['scope'] ?? '') {
            case 'entire_site': return true;
            case 'front_page': return !empty($context['frontPage']);
            case 'singular': return !empty($context['singular']);
            case 'archive': return !empty($context['archive']);
            case 'post_type': return ($context['postType'] ?? '') === ($condition['value'] ?? '');
            case 'logged_in': return !empty($context['loggedIn']);
            case 'logged_out': return empty($context['loggedIn']);
            default: return false;
        }
    }

    private function request_context(): array
    {
        return [
            'frontPage' => is_front_page(),
            'singular' => is_singular(),
            'archive' => is_archive() || is_home() || is_search(),
            'postType' => is_singular() ? (string) get_post_type((int) get_queried_object_id()) : '',
            'loggedIn' => is_user_logged_in(),
        ];
    }

    private function runtime_data(): array
    {
        $menus = [];
        $locations = get_nav_menu_locations();
        foreach (['primary', 'footer'] as $location) {
            $items = !empty($locations[$location]) ? wp_get_nav_menu_items((int) $locations[$location]) : [];
            $menus[$location] = is_array($items) ? array_map(static function ($item): array {
                return ['id' => (int) $item->ID, 'parentId' => (int) $item->menu_item_parent, 'label' => $item->title, 'url' => $item->url];
            }, $items) : [];
        }
        $logo_id = (int) get_theme_mod('custom_logo');
        $cart_count = function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
        return [
            'site' => [
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
                'logo' => $logo_id ? (string) wp_get_attachment_image_url($logo_id, 'full') : '',
            ],
            'menus' => $menus,
            'viewer' => ['loggedIn' => is_user_logged_in(), 'name' => is_user_logged_in() ? wp_get_current_user()->display_name : ''],
            'commerce' => [
                'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
                'cartCount' => $cart_count,
                'accountUrl' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(),
                'wishlistUrl' => home_url('/wishlist/'),
                'compareUrl' => home_url('/compare/'),
            ],
        ];
    }

    private function default_templates(): array
    {
        $header = $this->blank_document('header');
        $header['rows'][0]['columns'][0]['elements'] = [
            $this->element('logo', ['width' => 150, 'text' => '']),
            $this->element('menu', ['location' => 'primary', 'mobileDrawer' => true]),
            $this->element('search', ['placeholder' => 'جست‌وجو…']),
            $this->element('account', ['label' => 'حساب من']),
            $this->element('cart', ['label' => 'سبد خرید', 'showCount' => true]),
        ];
        $footer = $this->blank_document('footer');
        $footer['rows'][0]['style']['background'] = '#0f172a';
        $footer['rows'][0]['style']['color'] = '#e2e8f0';
        $footer['rows'][0]['columns'][0]['elements'] = [$this->element('copyright', ['text' => '© {year} {site_name}'])];
        $now = time();
        return [
            ['id' => 'woact_default_header', 'type' => 'header', 'title' => 'هدر پیش‌فرض', 'active' => false, 'priority' => 10, 'conditions' => [['scope' => 'entire_site', 'value' => '', 'exclude' => false]], 'draft' => $header, 'published' => null, 'revisions' => [], 'createdBy' => 0, 'updatedAt' => $now, 'publishedAt' => 0],
            ['id' => 'woact_default_footer', 'type' => 'footer', 'title' => 'فوتر پیش‌فرض', 'active' => false, 'priority' => 10, 'conditions' => [['scope' => 'entire_site', 'value' => '', 'exclude' => false]], 'draft' => $footer, 'published' => null, 'revisions' => [], 'createdBy' => 0, 'updatedAt' => $now, 'publishedAt' => 0],
        ];
    }

    private function blank_document(string $type): array
    {
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'type' => $type,
            'settings' => ['mobileBreakpoint' => 782, 'sticky' => $type === 'header', 'shrinkOnScroll' => false, 'className' => ''],
            'rows' => [[
                'id' => $this->new_id('row'),
                'columns' => [['id' => $this->new_id('column'), 'width' => 12, 'align' => $type === 'header' ? 'between' : 'center', 'elements' => []]],
                'style' => ['width' => 'contained', 'minHeight' => $type === 'header' ? 72 : 110, 'gap' => 20, 'background' => '#ffffff', 'color' => '#17202a', 'sticky' => false, 'overlay' => false],
                'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true],
            ]],
        ];
    }

    private function element(string $type, array $props): array
    {
        return ['id' => $this->new_id('element'), 'type' => $type, 'props' => $props, 'style' => ['color' => '', 'background' => '', 'fontSize' => 0, 'padding' => 0, 'radius' => 0], 'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true]];
    }

    private function sanitize_visibility(array $visibility): array
    {
        return [
            'desktop' => !array_key_exists('desktop', $visibility) || !empty($visibility['desktop']),
            'tablet' => !array_key_exists('tablet', $visibility) || !empty($visibility['tablet']),
            'mobile' => !array_key_exists('mobile', $visibility) || !empty($visibility['mobile']),
        ];
    }

    private function color(string $value, string $fallback): string
    {
        if ($value === '') {
            return $fallback;
        }
        $clean = sanitize_hex_color($value);
        return $clean ?: $fallback;
    }

    private function clean_id(string $id): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?: '';
        return $id !== '' ? $id : $this->new_id('node');
    }

    private function new_id(string $prefix): string
    {
        return $prefix . '_' . substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
    }

    private function with_revision(array $template): array
    {
        $template['revision'] = $this->get_revision_token($template);
        return $template;
    }

    private function persist(array $templates): void
    {
        update_option(self::OPTION, array_values($templates), false);
    }
}
