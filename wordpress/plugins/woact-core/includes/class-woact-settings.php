<?php
declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WOACT_TESTING')) {
    exit;
}

final class Woact_Settings
{
    public const OPTION = 'woact_settings';
    public const DRAFT_OPTION = 'woact_settings_draft';
    public const REVISIONS_OPTION = 'woact_settings_revisions';
    public const DB_VERSION_OPTION = 'woact_db_version';
    public const SCHEMA_VERSION = 2;

    private array $schema;
    private array $fields = [];
    private array $field_groups = [];

    public function __construct(array $schema)
    {
        $this->schema = $schema;
        foreach ((array) ($schema['groups'] ?? []) as $group) {
            foreach ((array) ($group['sections'] ?? []) as $section) {
                foreach ((array) ($section['fields'] ?? []) as $field) {
                    if (!empty($field['id'])) {
                        $this->fields[(string) $field['id']] = $field;
                        $this->field_groups[(string) $field['id']] = (string) $group['id'];
                    }
                }
            }
        }
    }

    public function get_schema(): array
    {
        return $this->schema;
    }

    public function get_defaults(): array
    {
        $defaults = [];
        foreach ($this->fields as $id => $field) {
            $defaults[$id] = $field['default'] ?? null;
        }
        return $defaults;
    }

    public function install_or_upgrade(): void
    {
        $existing = get_option(self::OPTION, null);
        if (!is_array($existing)) {
            $existing = $this->migrate_stage1_values();
        }

        $normalized = $this->sanitize(array_merge($this->get_defaults(), $existing));
        update_option(self::OPTION, $normalized, false);
        update_option(self::DB_VERSION_OPTION, self::SCHEMA_VERSION, false);
    }

    private function migrate_stage1_values(): array
    {
        $legacy = get_option('woact_stage1_settings', []);
        $values = $this->get_defaults();
        if (!is_array($legacy)) {
            return $values;
        }

        if (isset($legacy['render_mode'])) {
            $values['render.mode'] = $legacy['render_mode'];
        }
        if (isset($legacy['preserve_builders'])) {
            $values['render.preserve_builders'] = (bool) $legacy['preserve_builders'];
        }
        if (isset($legacy['react_post_types'])) {
            $values['render.post_types'] = (array) $legacy['react_post_types'];
        }
        return $values;
    }

    public function get_values(): array
    {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        return $this->sanitize(array_merge($this->get_defaults(), $saved));
    }

    public function get_revision_token(?array $values = null): string
    {
        $values = $values ?? $this->get_values();
        return substr(hash('sha256', (string) wp_json_encode($values)), 0, 20);
    }

    public function sanitize(array $input): array
    {
        $result = [];
        foreach ($this->fields as $id => $field) {
            $value = array_key_exists($id, $input) ? $input[$id] : ($field['default'] ?? null);
            $result[$id] = $this->sanitize_field($field, $value);
        }
        return $result;
    }

    private function sanitize_field(array $field, $value)
    {
        $type = (string) ($field['type'] ?? 'text');
        $default = $field['default'] ?? null;

        if ($type === 'switch') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($type, ['number', 'range'], true)) {
            $number = is_numeric($value) ? (float) $value : (float) $default;
            if (isset($field['min'])) {
                $number = max((float) $field['min'], $number);
            }
            if (isset($field['max'])) {
                $number = min((float) $field['max'], $number);
            }
            $step = (float) ($field['step'] ?? 1);
            return $step >= 1 ? (int) round($number) : $number;
        }

        if ($type === 'color') {
            $color = sanitize_hex_color((string) $value);
            return $color ?: (string) $default;
        }

        if (in_array($type, ['url', 'media'], true)) {
            return esc_url_raw((string) $value);
        }

        if ($type === 'textarea') {
            return sanitize_textarea_field((string) $value);
        }

        if ($type === 'code') {
            $code = str_replace("\0", '', (string) $value);
            return current_user_can('unfiltered_html') ? $code : sanitize_textarea_field($code);
        }

        if ($type === 'gradient') {
            $gradient = trim((string) $value);
            if (preg_match('/^(linear|radial)-gradient\([#%(),.\sa-zA-Z0-9-]+\)$/', $gradient)) {
                return $gradient;
            }
            return (string) $default;
        }

        if ($type === 'typography') {
            $value = is_array($value) ? $value : [];
            $fallback = is_array($default) ? $default : [];
            return [
                'family' => sanitize_text_field((string) ($value['family'] ?? $fallback['family'] ?? 'Tahoma')),
                'size' => max(8, min(120, (int) ($value['size'] ?? $fallback['size'] ?? 16))),
                'weight' => in_array((string) ($value['weight'] ?? ''), ['100', '200', '300', '400', '500', '600', '700', '800', '900'], true)
                    ? (string) $value['weight']
                    : (string) ($fallback['weight'] ?? '400'),
                'lineHeight' => max(1.0, min(3.0, (float) ($value['lineHeight'] ?? $fallback['lineHeight'] ?? 1.8))),
            ];
        }

        if ($type === 'repeater') {
            $rows = is_array($value) ? array_slice(array_values($value), 0, 50) : [];
            $columns = (array) ($field['columns'] ?? []);
            return array_map(function ($row) use ($columns): array {
                $row = is_array($row) ? $row : [];
                $clean = [];
                foreach ($columns as $column) {
                    $column_id = (string) ($column['id'] ?? '');
                    if ($column_id !== '') {
                        $clean[$column_id] = $this->sanitize_field($column, $row[$column_id] ?? '');
                    }
                }
                return $clean;
            }, $rows);
        }

        if (in_array($type, ['multiselect', 'sortable', 'post_types'], true)) {
            $items = array_values(array_unique(array_map('sanitize_key', is_array($value) ? $value : [])));
            if ($type === 'post_types') {
                $allowed = array_values(get_post_types(['public' => true], 'names'));
                $allowed = array_values(array_diff($allowed, ['attachment']));
            } else {
                $allowed = array_map(static fn(array $option): string => (string) $option['value'], (array) ($field['options'] ?? []));
            }
            return array_values(array_intersect($items, $allowed));
        }

        if (in_array($type, ['select', 'button_group'], true)) {
            $allowed = array_map(static fn(array $option): string => (string) $option['value'], (array) ($field['options'] ?? []));
            $candidate = sanitize_key((string) $value);
            return in_array($candidate, $allowed, true) ? $candidate : $default;
        }

        return sanitize_text_field((string) $value);
    }

    public function save(array $input, string $expected_revision, int $user_id)
    {
        $current = $this->get_values();
        $current_revision = $this->get_revision_token($current);
        if ($expected_revision !== '' && !hash_equals($current_revision, $expected_revision)) {
            return new WP_Error(
                'woact_settings_conflict',
                __('تنظیمات در پنجرهٔ دیگری تغییر کرده است. صفحه را تازه‌سازی کنید.', 'woact-core'),
                ['status' => 409, 'currentRevision' => $current_revision]
            );
        }

        $clean = $this->sanitize($input);
        if ($clean !== $current) {
            $this->push_revision($current, $user_id);
            update_option(self::OPTION, $clean, false);
        }
        delete_option(self::DRAFT_OPTION);

        return [
            'values' => $clean,
            'revision' => $this->get_revision_token($clean),
            'revisions' => $this->get_revision_summaries(),
        ];
    }

    public function save_draft(array $input, int $user_id): array
    {
        $draft = [
            'values' => $this->sanitize($input),
            'userId' => $user_id,
            'updatedAt' => time(),
        ];
        update_option(self::DRAFT_OPTION, $draft, false);
        return ['updatedAt' => $draft['updatedAt']];
    }

    public function get_draft(): ?array
    {
        $draft = get_option(self::DRAFT_OPTION, null);
        if (!is_array($draft) || empty($draft['values'])) {
            return null;
        }
        return [
            'values' => $this->sanitize((array) $draft['values']),
            'userId' => (int) ($draft['userId'] ?? 0),
            'updatedAt' => (int) ($draft['updatedAt'] ?? 0),
        ];
    }

    private function push_revision(array $values, int $user_id): void
    {
        $revisions = get_option(self::REVISIONS_OPTION, []);
        $revisions = is_array($revisions) ? $revisions : [];
        array_unshift($revisions, [
            'id' => wp_generate_uuid4(),
            'createdAt' => time(),
            'userId' => $user_id,
            'values' => $values,
        ]);
        $keep = max(5, min(100, (int) ($values['tools.keep_revisions'] ?? 20)));
        update_option(self::REVISIONS_OPTION, array_slice($revisions, 0, $keep), false);
    }

    public function get_revision_summaries(): array
    {
        $revisions = get_option(self::REVISIONS_OPTION, []);
        $revisions = is_array($revisions) ? $revisions : [];
        return array_map(static function (array $revision): array {
            $user = get_userdata((int) ($revision['userId'] ?? 0));
            return [
                'id' => (string) ($revision['id'] ?? ''),
                'createdAt' => (int) ($revision['createdAt'] ?? 0),
                'user' => $user ? $user->display_name : __('کاربر حذف‌شده', 'woact-core'),
            ];
        }, $revisions);
    }

    public function restore_revision(string $revision_id, int $user_id)
    {
        $revisions = get_option(self::REVISIONS_OPTION, []);
        $revisions = is_array($revisions) ? $revisions : [];
        foreach ($revisions as $revision) {
            if (($revision['id'] ?? '') === $revision_id && is_array($revision['values'] ?? null)) {
                $current = $this->get_values();
                $this->push_revision($current, $user_id);
                $values = $this->sanitize($revision['values']);
                update_option(self::OPTION, $values, false);
                delete_option(self::DRAFT_OPTION);
                return [
                    'values' => $values,
                    'revision' => $this->get_revision_token($values),
                    'revisions' => $this->get_revision_summaries(),
                ];
            }
        }
        return new WP_Error('woact_revision_missing', __('نسخهٔ انتخاب‌شده پیدا نشد.', 'woact-core'), ['status' => 404]);
    }

    public function reset(?string $group_id, int $user_id): array
    {
        $current = $this->get_values();
        $defaults = $this->get_defaults();
        $next = $current;

        if ($group_id === null || $group_id === '') {
            $next = $defaults;
        } else {
            foreach ($this->field_groups as $field_id => $field_group) {
                if ($field_group === $group_id) {
                    $next[$field_id] = $defaults[$field_id];
                }
            }
        }

        $this->push_revision($current, $user_id);
        $next = $this->sanitize($next);
        update_option(self::OPTION, $next, false);
        delete_option(self::DRAFT_OPTION);
        return [
            'values' => $next,
            'revision' => $this->get_revision_token($next),
            'revisions' => $this->get_revision_summaries(),
        ];
    }

    public function import(array $document, string $expected_revision, int $user_id)
    {
        if ((int) ($document['schemaVersion'] ?? 0) > self::SCHEMA_VERSION) {
            return new WP_Error('woact_import_newer_schema', __('این فایل با نسخهٔ جدیدتری از Woact ساخته شده است.', 'woact-core'), ['status' => 422]);
        }
        if (!is_array($document['values'] ?? null)) {
            return new WP_Error('woact_import_invalid', __('ساختار فایل تنظیمات معتبر نیست.', 'woact-core'), ['status' => 422]);
        }
        return $this->save($document['values'], $expected_revision, $user_id);
    }

    public function get_presets(): array
    {
        return [
            [
                'id' => 'clean',
                'label' => 'تمیز و مینیمال',
                'values' => [
                    'layout.container_width' => 1120,
                    'layout.card_radius' => 8,
                    'style.primary' => '#111827',
                    'style.accent' => '#4f46e5',
                    'style.button_radius' => 6,
                ],
            ],
            [
                'id' => 'corporate',
                'label' => 'شرکتی',
                'values' => [
                    'layout.container_width' => 1240,
                    'layout.card_radius' => 12,
                    'style.primary' => '#1d4ed8',
                    'style.accent' => '#0ea5e9',
                    'page_title.height' => 260,
                ],
            ],
            [
                'id' => 'commerce',
                'label' => 'فروشگاهی',
                'values' => [
                    'layout.container_width' => 1320,
                    'style.primary' => '#0f766e',
                    'style.accent' => '#f59e0b',
                    'shop.columns_desktop' => 4,
                    'shop.quick_view' => true,
                    'shop.wishlist' => true,
                    'cart.drawer' => true,
                ],
            ],
            [
                'id' => 'performance',
                'label' => 'حداکثر کارایی',
                'values' => [
                    'performance.minify_css' => true,
                    'performance.defer_js' => true,
                    'performance.conditional_assets' => true,
                    'performance.disable_emojis' => true,
                    'performance.lazy_images' => true,
                    'performance.lazy_iframes' => true,
                    'performance.preloader' => false,
                ],
            ],
        ];
    }
}

