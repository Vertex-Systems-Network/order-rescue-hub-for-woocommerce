<?php

namespace WPEssential\OrderRescueHub\Pro;

use WPEssential\OrderRescueHub\Repository\CaseRepository;
use WP_Error;
use WP_REST_Request;

final class Controller
{
    private CaseRepository $repository;
    private AssignmentService $assignment;
    private AlertService $alerts;

    public function __construct(CaseRepository $repository, AssignmentService $assignment, AlertService $alerts)
    {
        $this->repository = $repository;
        $this->assignment = $assignment;
        $this->alerts = $alerts;
    }

    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('wperh/v1', '/pro/settings', array(
                array('methods' => 'GET', 'callback' => array($this, 'get_settings'), 'permission_callback' => array($this, 'can_manage_settings')),
                array('methods' => 'POST', 'callback' => array($this, 'save_settings'), 'permission_callback' => array($this, 'can_manage_settings')),
            ));
            register_rest_route('wperh/v1', '/pro/users', array(
                'methods' => 'GET', 'callback' => array($this, 'users'), 'permission_callback' => array($this, 'can_manage_cases'),
            ));
            register_rest_route('wperh/v1', '/pro/alerts/test', array(
                'methods' => 'POST', 'callback' => array($this, 'test_alert'), 'permission_callback' => array($this, 'can_manage_settings'),
            ));
            register_rest_route('wperh/v1', '/cases/(?P<id>\d+)/assignment', array(
                'methods' => 'POST', 'callback' => array($this, 'assign'), 'permission_callback' => array($this, 'can_manage_cases'),
            ));
        });
    }

    public function can_manage_settings(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function can_manage_cases(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function get_settings()
    {
        return rest_ensure_response(Settings::get());
    }

    public function save_settings(WP_REST_Request $request)
    {
        $data = $request->get_json_params();
        if (!is_array($data)) {
            return new WP_Error('wperh_settings_invalid', __('Settings payload is invalid.', 'wpessential-order-rescue-hub'), array('status' => 400));
        }
        return rest_ensure_response(Settings::save($data));
    }

    public function users()
    {
        $users = get_users(array('capability' => 'manage_woocommerce', 'number' => 100, 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array('ID', 'display_name')));
        $items = array_map(static function ($user): array {
            return array('id' => (int) $user->ID, 'name' => sanitize_text_field($user->display_name));
        }, $users);
        return rest_ensure_response($items);
    }

    public function assign(WP_REST_Request $request)
    {
        $case = $this->repository->find(absint($request['id']));
        if (!$case) {
            return new WP_Error('wperh_case_missing', __('Case not found.', 'wpessential-order-rescue-hub'), array('status' => 404));
        }
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_shop_order', (int) $case['order_id'])) {
            return new WP_Error('wperh_forbidden', __('You cannot manage this order.', 'wpessential-order-rescue-hub'), array('status' => 403));
        }
        $result = $this->assignment->assign((int) $case['case_id'], absint($request->get_param('user_id')));
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public function test_alert()
    {
        return $this->alerts->test_delivery()
            ? rest_ensure_response(array('sent' => true))
            : new WP_Error('wperh_alert_failed', __('The test alert could not be sent. Check WordPress mail delivery and try again.', 'wpessential-order-rescue-hub'), array('status' => 502));
    }
}
