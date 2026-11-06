<?php

namespace WPEssential\OrderRescueHub\Service;

use WPEssential\OrderRescueHub\Repository\CaseRepository;
use WC_Order;

final class Detector
{
    private CaseRepository $repository;

    public function __construct(CaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('woocommerce_new_order', array($this, 'on_order_change'), 20, 1);
        add_action('woocommerce_update_order', array($this, 'on_order_change'), 20, 1);
        add_action('woocommerce_order_status_changed', array($this, 'on_order_change'), 20, 1);
        add_action('woocommerce_reduce_order_stock', array($this, 'on_order_object'), 20, 1);
    }

    public function on_order_change($order_id): void
    {
        $this->evaluate(absint($order_id));
    }

    public function on_order_object($order): void
    {
        if ($order instanceof WC_Order) {
            $this->evaluate((int) $order->get_id());
        }
    }

    public function evaluate(int $order_id): void
    {
        if ($order_id < 1 || !function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        $now = time();
        $created = $order->get_date_created();
        $age_hours = $created ? max(0, (int) floor(($now - $created->getTimestamp()) / HOUR_IN_SECONDS)) : 0;
        $status = $order->get_status();
        $reasons = array();
        $evidence = array(
            'status' => sanitize_key($status),
            'payment_method' => sanitize_key($order->get_payment_method()),
            'shipping_missing' => false,
            'processing_age_hours' => 0,
            'stock_issue' => false,
        );

        if ('failed' === $status) {
            $reasons[] = 'failed_payment';
        } elseif ('pending' === $status && $age_hours >= 1) {
            $reasons[] = 'pending_payment';
        }

        if ('processing' === $status && $age_hours >= 72) {
            $reasons[] = 'processing_delay';
            $evidence['processing_age_hours'] = $age_hours;
        }

        if ($this->requires_shipping($order)) {
            $missing = '' === trim((string) $order->get_shipping_address_1())
                || '' === trim((string) $order->get_shipping_city())
                || '' === trim((string) $order->get_shipping_country());
            if ($missing) {
                $reasons[] = 'missing_shipping';
                $evidence['shipping_missing'] = true;
            }
        }

        if ($this->has_stock_issue($order)) {
            $reasons[] = 'stock_discrepancy';
            $evidence['stock_issue'] = true;
        }

        if ($reasons) {
            $this->repository->detect($order_id, $reasons, $evidence);
        }
    }

    private function requires_shipping(WC_Order $order): bool
    {
        foreach ($order->get_items('line_item') as $item) {
            $product = $item->get_product();
            if ($product && $product->needs_shipping()) {
                return true;
            }
        }

        return false;
    }

    private function has_stock_issue(WC_Order $order): bool
    {
        foreach ($order->get_items('line_item') as $item) {
            $product = $item->get_product();
            if (!$product) {
                return true;
            }

            if ($product->managing_stock() && !$product->backorders_allowed()) {
                $stock = $product->get_stock_quantity();
                if (null !== $stock && $stock < 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
