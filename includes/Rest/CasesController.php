<?php

namespace WPEssential\OrderRescueHub\Rest;

use WPEssential\OrderRescueHub\Repository\CaseRepository;
use WPEssential\OrderRescueHub\Service\CaseService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CasesController
{
    private CaseRepository $repository;
    private CaseService $service;

    public function __construct(CaseRepository $repository, CaseService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    public function register(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('wperh/v1', '/cases', array(
                'methods' => 'GET',
                'callback' => array($this, 'list_cases'),
                'permission_callback' => array($this, 'can_manage'),
                'args' => array(
                    'state' => array('sanitize_callback' => 'sanitize_key'),
                    'reason' => array('sanitize_callback' => 'sanitize_key'),
                    'search' => array('sanitize_callback' => 'sanitize_text_field'),
                    'older_than_hours' => array('sanitize_callback' => 'absint'),
                    'page' => array('sanitize_callback' => 'absint'),
                    'per_page' => array('sanitize_callback' => 'absint'),
                ),
            ));

            register_rest_route('wperh/v1', '/cases/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_case'),
                'permission_callback' => array($this, 'can_manage'),
            ));

            register_rest_route('wperh/v1', '/cases/(?P<id>\d+)/notes', array(
                'methods' => 'POST',
                'callback' => array($this, 'add_note'),
                'permission_callback' => array($this, 'can_manage'),
            ));

            register_rest_route('wperh/v1', '/cases/(?P<id>\d+)/transition', array(
                'methods' => 'POST',
                'callback' => array($this, 'transition'),
                'permission_callback' => array($this, 'can_manage'),
            ));
        });
    }

    public function can_manage(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function list_cases(WP_REST_Request $request)
    {
        $state = sanitize_key((string) $request->get_param('state'));
        $allowed_states = array('all', 'open', 'triaged', 'waiting', 'assigned', 'escalated', 'closed');
        if ($state && !in_array($state, $allowed_states, true)) {
            return new WP_Error('wperh_filter_invalid', __('Invalid case-state filter.', 'wpessential-order-rescue-hub'), array('status' => 400));
        }

        $data = $this->repository->list(array(
            'state' => $state ?: 'open',
            'reason' => sanitize_key((string) $request->get_param('reason')),
            'search' => sanitize_text_field((string) $request->get_param('search')),
            'older_than_hours' => absint($request->get_param('older_than_hours')),
            'page' => max(1, absint($request->get_param('page'))),
            'per_page' => min(100, max(1, absint($request->get_param('per_page') ?: 25))),
        ));

        return rest_ensure_response($data);
    }

    public function get_case(WP_REST_Request $request)
    {
        $case = $this->repository->find(absint($request['id']));
        if (!$case) {
            return new WP_Error('wperh_case_missing', __('Case not found.', 'wpessential-order-rescue-hub'), array('status' => 404));
        }
        if (!$this->can_access_order((int) $case['order_id'])) {
            return new WP_Error('wperh_forbidden', __('You cannot view this order.', 'wpessential-order-rescue-hub'), array('status' => 403));
        }

        $case['order'] = $this->order_snapshot((int) $case['order_id']);
        $case['events'] = $this->present_events($this->repository->events((int) $case['case_id']));
        return rest_ensure_response($case);
    }

    public function add_note(WP_REST_Request $request)
    {
        $case = $this->repository->find(absint($request['id']));
        if (!$case) {
            return new WP_Error('wperh_case_missing', __('Case not found.', 'wpessential-order-rescue-hub'), array('status' => 404));
        }
        if (!$this->can_access_order((int) $case['order_id'])) {
            return new WP_Error('wperh_forbidden', __('You cannot view this order.', 'wpessential-order-rescue-hub'), array('status' => 403));
        }

        $result = $this->service->add_note((int) $case['case_id'], (string) $request->get_param('note'));
        return is_wp_error($result) ? $result : $this->get_case($request);
    }

    public function transition(WP_REST_Request $request)
    {
        $case = $this->repository->find(absint($request['id']));
        if (!$case) {
            return new WP_Error('wperh_case_missing', __('Case not found.', 'wpessential-order-rescue-hub'), array('status' => 404));
        }
        if (!$this->can_access_order((int) $case['order_id'])) {
            return new WP_Error('wperh_forbidden', __('You cannot manage this order.', 'wpessential-order-rescue-hub'), array('status' => 403));
        }

        $result = $this->service->transition(
            (int) $case['case_id'],
            sanitize_key((string) $request->get_param('state')),
            sanitize_key((string) $request->get_param('outcome')),
            absint($request->get_param('row_version'))
        );

        return is_wp_error($result) ? $result : $this->get_case($request);
    }

    private function can_access_order(int $order_id): bool
    {
        return current_user_can('manage_woocommerce') || current_user_can('edit_shop_order', $order_id);
    }

    private function order_snapshot(int $order_id): ?array
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return null;
        }

        $items = array();
        foreach ($order->get_items('line_item') as $item) {
            $items[] = array(
                'name' => sanitize_text_field($item->get_name()),
                'quantity' => (int) $item->get_quantity(),
            );
        }

        $shipping_missing = false;
        foreach ($order->get_items('line_item') as $item) {
            $product = $item->get_product();
            if ($product && $product->needs_shipping()) {
                $shipping_missing = '' === trim((string) $order->get_shipping_address_1())
                    || '' === trim((string) $order->get_shipping_city())
                    || '' === trim((string) $order->get_shipping_country());
                break;
            }
        }

        return array(
            'number' => (string) $order->get_order_number(),
            'status' => sanitize_key($order->get_status()),
            'date' => $order->get_date_created() ? $order->get_date_created()->date_i18n('Y-m-d H:i') : '',
            'payment_method' => sanitize_text_field($order->get_payment_method_title()),
            'total' => (string) wc_format_localized_price($order->get_total()),\n            'currency' => sanitize_text_field($order->get_currency()),
            'shipping_missing' => $shipping_missing,
            'items' => $items,
        );
    }

    private function present_events(array $events): array
    {
        foreach ($events as &$event) {
            $event['actor_name'] = '';
            if (!empty($event['actor_id'])) {
                $actor = get_userdata((int) $event['actor_id']);
                $event['actor_name'] = $actor ? $actor->display_name : __('Former user', 'wpessential-order-rescue-hub');
            }
        }

        return $events;
    }
}
