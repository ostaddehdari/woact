<?php
declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WOACT_TESTING')) {
    exit;
}

final class Woact_Render_Policy
{
    public static function decide(array $context): string
    {
        $global_mode = (string) ($context['global_mode'] ?? 'hybrid');
        $override = (string) ($context['override'] ?? 'inherit');
        $is_builder_document = (bool) ($context['is_builder_document'] ?? false);
        $preserve_builders = (bool) ($context['preserve_builders'] ?? true);
        $post_type = (string) ($context['post_type'] ?? '');
        $enabled_post_types = (array) ($context['enabled_post_types'] ?? []);

        if ($global_mode === 'classic' || $override === 'classic') {
            return 'classic';
        }

        if ($override === 'react') {
            return 'react';
        }

        if ($preserve_builders && $is_builder_document) {
            return 'classic';
        }

        if ($global_mode === 'react' && in_array($post_type, $enabled_post_types, true)) {
            return 'react';
        }

        return 'classic';
    }
}

