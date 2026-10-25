<?php
declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WOACT_TESTING')) exit;

return [
    'type' => 'notice_card',
    'label' => 'کارت اعلان',
    'icon' => 'megaphone',
    'category' => 'business_widgets',
    'version' => '1.1.0',
    'defaults' => [
        'eyebrow' => 'اطلاعیه',
        'title' => 'عنوان اعلان مهم',
        'text' => 'متن کوتاه و روشن اعلان را اینجا بنویسید.',
        'tone' => 'brand',
        'buttonLabel' => 'بیشتر بدانید',
        'buttonUrl' => '#',
    ],
    'fields' => [
        ['id' => 'eyebrow', 'label' => 'روتیتر', 'type' => 'text', 'default' => 'اطلاعیه'],
        ['id' => 'title', 'label' => 'عنوان', 'type' => 'text', 'default' => 'عنوان اعلان مهم'],
        ['id' => 'text', 'label' => 'توضیحات', 'type' => 'textarea', 'default' => ''],
        ['id' => 'tone', 'label' => 'حالت رنگی', 'type' => 'select', 'default' => 'brand', 'options' => ['brand' => 'برند', 'success' => 'موفق', 'warning' => 'هشدار']],
        ['id' => 'buttonLabel', 'label' => 'متن دکمه', 'type' => 'text', 'default' => 'بیشتر بدانید'],
        ['id' => 'buttonUrl', 'label' => 'پیوند دکمه', 'type' => 'url', 'default' => '#'],
    ],
    'assets' => ['notice-card'],
    'dependencies' => [],
    'migrations' => [
        '1.1.0' => static function (array $props): array {
            if (isset($props['description']) && !isset($props['text'])) $props['text'] = $props['description'];
            unset($props['description']);
            return $props;
        },
    ],
];
