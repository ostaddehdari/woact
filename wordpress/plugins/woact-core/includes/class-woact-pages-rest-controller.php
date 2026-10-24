<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

final class Woact_Pages_REST_Controller
{
    private Woact_Pages $pages;
    public function __construct(Woact_Pages $pages) { $this->pages = $pages; }
    public function permissions_check(WP_REST_Request $request): bool
    {
        $id = (int) ($request['id'] ?? 0);
        return $id ? current_user_can('edit_post', $id) : (current_user_can('edit_posts') || current_user_can('edit_pages'));
    }

    public function register_routes(): void
    {
        register_rest_route('woact/v1', '/admin/pages', [[
            'methods' => WP_REST_Server::READABLE, 'permission_callback' => [$this, 'permissions_check'], 'callback' => [$this, 'index'],
        ], [
            'methods' => WP_REST_Server::CREATABLE, 'permission_callback' => [$this, 'permissions_check'], 'callback' => [$this, 'create'],
        ]]);
        register_rest_route('woact/v1', '/admin/pages/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'permission_callback' => [$this, 'permissions_check'], 'callback' => [$this, 'show']],
            ['methods' => WP_REST_Server::EDITABLE, 'permission_callback' => [$this, 'permissions_check'], 'callback' => [$this, 'update']],
        ]);
        foreach (['publish', 'restore'] as $action) {
            register_rest_route('woact/v1', '/admin/pages/(?P<id>\d+)/' . $action, [
                'methods' => WP_REST_Server::CREATABLE, 'permission_callback' => [$this, 'permissions_check'], 'callback' => [$this, $action],
            ]);
        }
    }

    public function index(): WP_REST_Response
    {
        return new WP_REST_Response(['pages' => $this->pages->list_pages(), 'manifest' => $this->pages->get_manifest(), 'version' => WOACT_CORE_VERSION]);
    }
    public function create(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $result = $this->pages->create((string) ($params['title'] ?? ''), get_current_user_id(), sanitize_key((string) ($params['postType'] ?? 'page')));
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result), 201);
    }
    public function show(WP_REST_Request $request)
    {
        $page = $this->pages->get((int) $request['id']);
        return $page ? new WP_REST_Response($this->expose($page)) : new WP_Error('woact_page_missing', __('برگه پیدا نشد.', 'woact-core'), ['status' => 404]);
    }
    public function update(WP_REST_Request $request)
    {
        $params = (array) $request->get_json_params();
        $result = $this->pages->save((int) $request['id'], $params, sanitize_text_field((string) ($params['revision'] ?? '')));
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result));
    }
    public function publish(WP_REST_Request $request)
    {
        $result = $this->pages->publish((int) $request['id'], get_current_user_id());
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result));
    }
    public function restore(WP_REST_Request $request)
    {
        $params = (array) $request->get_json_params();
        $result = $this->pages->restore((int) $request['id'], sanitize_text_field((string) ($params['revisionId'] ?? '')));
        return is_wp_error($result) ? $result : new WP_REST_Response($this->expose($result));
    }
    private function expose(array $page): array
    {
        $page['revisions'] = array_map(static function (array $revision): array {
            $user = get_userdata((int) ($revision['userId'] ?? 0));
            return ['id' => (string) ($revision['id'] ?? ''), 'createdAt' => (int) ($revision['createdAt'] ?? 0), 'user' => $user ? $user->display_name : '—'];
        }, (array) ($page['revisions'] ?? []));
        return $page;
    }
}
