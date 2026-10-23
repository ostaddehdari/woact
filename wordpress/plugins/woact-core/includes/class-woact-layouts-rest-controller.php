<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class Woact_Layouts_REST_Controller
{
    private Woact_Layouts $layouts;

    public function __construct(Woact_Layouts $layouts)
    {
        $this->layouts = $layouts;
    }

    public function register_routes(): void
    {
        register_rest_route('woact/v1', '/admin/layouts', [
            [
                'methods' => WP_REST_Server::READABLE,
                'permission_callback' => [$this, 'permissions_check'],
                'callback' => [$this, 'index'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'permission_callback' => [$this, 'permissions_check'],
                'callback' => [$this, 'create'],
            ],
        ]);
        register_rest_route('woact/v1', '/admin/layouts/import', [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => [$this, 'permissions_check'],
            'callback' => [$this, 'import'],
        ]);
        register_rest_route('woact/v1', '/admin/layouts/(?P<id>[a-zA-Z0-9_-]+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'permission_callback' => [$this, 'permissions_check'],
                'callback' => [$this, 'update'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'permission_callback' => [$this, 'permissions_check'],
                'callback' => [$this, 'delete'],
            ],
        ]);
        foreach (['publish', 'duplicate', 'restore'] as $action) {
            register_rest_route('woact/v1', '/admin/layouts/(?P<id>[a-zA-Z0-9_-]+)/' . $action, [
                'methods' => WP_REST_Server::CREATABLE,
                'permission_callback' => [$this, 'permissions_check'],
                'callback' => [$this, $action],
            ]);
        }
    }

    public function permissions_check(): bool
    {
        return current_user_can('manage_options');
    }

    public function index(): WP_REST_Response
    {
        $templates = array_map([$this, 'expose'], $this->layouts->get_templates());
        return new WP_REST_Response([
            'schemaVersion' => Woact_Layouts::SCHEMA_VERSION,
            'templates' => $templates,
            'manifest' => $this->layouts->get_manifest(),
            'runtimeData' => $this->layouts->get_runtime_data(),
            'postTypes' => $this->post_types(),
            'sampleUrl' => plugins_url('examples/woact-ai-header-footer-example.json', WOACT_CORE_FILE),
            'previewUrl' => add_query_arg([
                'action' => 'woact_layout_preview',
                '_wpnonce' => wp_create_nonce('woact_layout_preview'),
            ], admin_url('admin-post.php')),
            'version' => WOACT_CORE_VERSION,
        ]);
    }

    public function create(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $result = $this->layouts->create(
            sanitize_key((string) ($params['type'] ?? '')),
            sanitize_text_field((string) ($params['title'] ?? '')),
            get_current_user_id()
        );
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result), 201);
    }

    public function update(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $result = $this->layouts->save_draft(
            sanitize_key((string) $request['id']),
            is_array($params) ? $params : [],
            sanitize_text_field((string) ($params['revision'] ?? '')),
            get_current_user_id()
        );
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result));
    }

    public function publish(WP_REST_Request $request)
    {
        $result = $this->layouts->publish(sanitize_key((string) $request['id']), get_current_user_id());
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result));
    }

    public function duplicate(WP_REST_Request $request)
    {
        $result = $this->layouts->duplicate(sanitize_key((string) $request['id']), get_current_user_id());
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result), 201);
    }

    public function restore(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $result = $this->layouts->restore(
            sanitize_key((string) $request['id']),
            sanitize_text_field((string) ($params['revisionId'] ?? '')),
            get_current_user_id()
        );
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result));
    }

    public function delete(WP_REST_Request $request)
    {
        if (!$this->layouts->delete(sanitize_key((string) $request['id']))) {
            return new WP_Error('woact_layout_missing', __('قالب پیدا نشد.', 'woact-core'), ['status' => 404]);
        }
        return new WP_REST_Response(null, 204);
    }

    public function import(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $document = is_array($params['document'] ?? null) ? $params['document'] : [];
        $sources = is_array($document['templates'] ?? null) ? $document['templates'] : [];
        if (!$sources && is_array($document['template'] ?? null)) {
            $sources = [$document['template']];
        }
        if (!$sources) {
            return new WP_Error('woact_layout_import', __('فایل قالب معتبر نیست.', 'woact-core'), ['status' => 422]);
        }
        $imported = [];
        foreach (array_slice($sources, 0, 20) as $source) {
            if (!is_array($source)) {
                continue;
            }
            $result = $this->layouts->import([
                'schemaVersion' => (int) ($document['schemaVersion'] ?? 0),
                'template' => $source,
            ], get_current_user_id());
            if (is_wp_error($result)) {
                return $result;
            }
            $imported[] = $this->expose($result);
        }
        return new WP_REST_Response(['templates' => $imported, 'count' => count($imported)], 201);
    }

    public function expose(array $template): array
    {
        $template['revision'] = $this->layouts->get_revision_token($template);
        $template['revisions'] = array_map(static function (array $revision): array {
            $user = get_userdata((int) ($revision['userId'] ?? 0));
            return [
                'id' => (string) ($revision['id'] ?? ''),
                'createdAt' => (int) ($revision['createdAt'] ?? 0),
                'user' => $user ? $user->display_name : __('کاربر حذف‌شده', 'woact-core'),
            ];
        }, (array) ($template['revisions'] ?? []));
        return $template;
    }

    private function post_types(): array
    {
        $types = get_post_types(['public' => true, 'show_ui' => true], 'objects');
        unset($types['attachment']);
        return array_values(array_map(static fn($type): array => ['value' => $type->name, 'label' => $type->labels->name], $types));
    }
}
