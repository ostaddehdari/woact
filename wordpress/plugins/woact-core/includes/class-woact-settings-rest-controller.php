<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class Woact_Settings_REST_Controller
{
    private Woact_Settings $settings;

    public function __construct(Woact_Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register_routes(): void
    {
        register_rest_route('woact/v1', '/admin/settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'permission_callback' => [$this, 'permissions_check'],
                'callback' => [$this, 'get_item'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'permission_callback' => [$this, 'permissions_check'],
                'callback' => [$this, 'update_item'],
            ],
        ]);

        register_rest_route('woact/v1', '/admin/settings/draft', [
            'methods' => WP_REST_Server::EDITABLE,
            'permission_callback' => [$this, 'permissions_check'],
            'callback' => [$this, 'save_draft'],
        ]);

        register_rest_route('woact/v1', '/admin/settings/import', [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => [$this, 'permissions_check'],
            'callback' => [$this, 'import_item'],
        ]);

        register_rest_route('woact/v1', '/admin/settings/reset', [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => [$this, 'permissions_check'],
            'callback' => [$this, 'reset_item'],
        ]);

        register_rest_route('woact/v1', '/admin/settings/restore', [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => [$this, 'permissions_check'],
            'callback' => [$this, 'restore_item'],
        ]);
    }

    public function permissions_check(): bool
    {
        return current_user_can('manage_options');
    }

    public function get_item(): WP_REST_Response
    {
        $values = $this->settings->get_values();
        $post_types = get_post_types(['public' => true, 'show_ui' => true], 'objects');
        unset($post_types['attachment']);
        $post_type_options = [];
        foreach ($post_types as $post_type) {
            $post_type_options[] = [
                'value' => $post_type->name,
                'label' => $post_type->labels->name,
            ];
        }

        return new WP_REST_Response([
            'schema' => $this->settings->get_schema(),
            'values' => $values,
            'revision' => $this->settings->get_revision_token($values),
            'draft' => $this->settings->get_draft(),
            'revisions' => $this->settings->get_revision_summaries(),
            'presets' => $this->settings->get_presets(),
            'context' => [
                'postTypes' => $post_type_options,
                'siteName' => get_bloginfo('name'),
                'version' => WOACT_CORE_VERSION,
            ],
        ]);
    }

    public function update_item(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $result = $this->settings->save(
            is_array($params['values'] ?? null) ? $params['values'] : [],
            sanitize_text_field((string) ($params['revision'] ?? '')),
            get_current_user_id()
        );
        return is_wp_error($result) ? $result : new WP_REST_Response($result);
    }

    public function save_draft(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params();
        return new WP_REST_Response($this->settings->save_draft(
            is_array($params['values'] ?? null) ? $params['values'] : [],
            get_current_user_id()
        ));
    }

    public function import_item(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $result = $this->settings->import(
            is_array($params['document'] ?? null) ? $params['document'] : [],
            sanitize_text_field((string) ($params['revision'] ?? '')),
            get_current_user_id()
        );
        return is_wp_error($result) ? $result : new WP_REST_Response($result);
    }

    public function reset_item(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params();
        $group = isset($params['group']) ? sanitize_key((string) $params['group']) : null;
        return new WP_REST_Response($this->settings->reset($group, get_current_user_id()));
    }

    public function restore_item(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $result = $this->settings->restore_revision(
            sanitize_text_field((string) ($params['revisionId'] ?? '')),
            get_current_user_id()
        );
        return is_wp_error($result) ? $result : new WP_REST_Response($result);
    }
}

