<?php
declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WOACT_TESTING')) exit;

/** Shared, schema-driven control definitions for every Woact editor and widget. */
final class Woact_Control_Schema
{
    public const VERSION = 1;
    public const TYPES = [
        'text', 'textarea', 'url', 'code', 'number', 'slider', 'select', 'multiselect',
        'checkbox', 'switch', 'radio', 'button_group', 'unit', 'dimensions', 'color',
        'gradient', 'typography', 'background', 'border', 'shadow', 'media', 'icon',
        'repeater', 'nested_repeater',
    ];

    public static function manifest(): array
    {
        return [
            'version' => self::VERSION,
            'types' => self::TYPES,
            'units' => ['px', '%', 'em', 'rem', 'vw', 'vh'],
            'states' => ['normal', 'hover', 'focus', 'active', 'disabled'],
            'devices' => ['desktop', 'tablet', 'mobile'],
            'conditionOperators' => ['equals', 'not', 'in', 'gt', 'lt', 'and', 'or'],
            'groups' => self::common_groups(),
        ];
    }

    public static function common_groups(): array
    {
        return [
            'layout' => [
                ['id' => 'margin', 'label' => 'فاصله بیرونی', 'type' => 'dimensions', 'responsive' => true],
                ['id' => 'padding', 'label' => 'فاصله داخلی', 'type' => 'dimensions', 'responsive' => true],
                ['id' => 'width', 'label' => 'عرض', 'type' => 'unit', 'responsive' => true],
                ['id' => 'minHeight', 'label' => 'حداقل ارتفاع', 'type' => 'unit', 'responsive' => true],
                ['id' => 'zIndex', 'label' => 'Z-index', 'type' => 'number', 'responsive' => true],
            ],
            'style' => [
                ['id' => 'textColor', 'label' => 'رنگ متن', 'type' => 'color', 'states' => ['normal', 'hover']],
                ['id' => 'typography', 'label' => 'تایپوگرافی', 'type' => 'typography', 'responsive' => true],
                ['id' => 'background', 'label' => 'پس‌زمینه', 'type' => 'background', 'states' => ['normal', 'hover']],
                ['id' => 'border', 'label' => 'حاشیه و سایه', 'type' => 'border', 'states' => ['normal', 'hover']],
            ],
            'advanced' => [
                ['id' => 'cssId', 'label' => 'شناسه CSS', 'type' => 'text'],
                ['id' => 'cssClasses', 'label' => 'کلاس‌های CSS', 'type' => 'text'],
                ['id' => 'attributes', 'label' => 'ویژگی‌ها', 'type' => 'repeater', 'itemDefaults' => ['key' => '', 'value' => '']],
                ['id' => 'customCss', 'label' => 'CSS سفارشی', 'type' => 'code'],
            ],
        ];
    }

    public static function sanitize_field(array $field): array
    {
        $type = sanitize_key((string) ($field['type'] ?? 'text'));
        return [
            'id' => sanitize_key((string) ($field['id'] ?? '')),
            'label' => sanitize_text_field((string) ($field['label'] ?? '')),
            'type' => in_array($type, self::TYPES, true) ? $type : 'text',
            'default' => $field['default'] ?? null,
            'responsive' => !empty($field['responsive']),
            'condition' => is_array($field['condition'] ?? null) ? $field['condition'] : [],
            'options' => is_array($field['options'] ?? null) ? $field['options'] : [],
        ];
    }
}
