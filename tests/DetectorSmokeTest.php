<?php

namespace WPEssential\\OrderRescueHub\\Repository {
    final class CaseRepository
    {
        public array $detected = array();
        public array $resolved = array();

        public function detect(int $order_id, array $reasons, array $evidence): void
        {
            $this->detected[] = array($order_id, $reasons, $evidence);
        }

        public function resolve_if_clear(int $order_id): void
        {
            $this->resolved[] = $order_id;
        }
    }
}

namespace {
    if (!defined('HOUR_IN_SECONDS')) {
        define('HOUR_IN_SECONDS', 3600);
    }

    final class WC_Order
    {
        private string $status;
        private array $items;

        public function __construct(string $status, array $items = array())
        {
            $this->status = $status;
            $this->items = $items;
        }

        public function get_date_created()
        {
            return new class {
                public function getTimestamp(): int
                {
                    return time() - (2 * HOUR_IN_SECONDS);
                }
            };
        }

        public function get_status(): string
        {
            return $this->status;
        }

        public function get_payment_method(): string
        {
            return 'cod';
        }

        public function get_items(string $type = 'line_item'): array
        {
            return $this->items;
        }

        public function get_shipping_address_1(): string
        {
            return '';
        }

        public function get_shipping_city(): string
        {
            return '';
        }

        public function get_shipping_country(): string
        {
            return '';
        }
    }

    final class DetectorSmokeProduct
    {
        private bool $shipping;

        public function __construct(bool $shipping = false)
        {
            $this->shipping = $shipping;
        }

        public function needs_shipping(): bool
        {
            return $this->shipping;
        }

        public function managing_stock(): bool
        {
            return false;
        }

        public function backorders_allowed(): bool
        {
            return false;
        }

        public function get_stock_quantity()
        {
            return null;
        }
    }

    final class DetectorSmokeItem
    {
        private $product;

        public function __construct($product)
        {
            $this->product = $product;
        }

        public function get_product()
        {
            return $this->product;
        }
    }

    $GLOBALS['wperh_test_orders'] = array();
    $GLOBALS['wperh_test_options'] = array();
    $GLOBALS['wperh_test_order_query'] = array();

    function wc_get_order(int $order_id)
    {
        return $GLOBALS['wperh_test_orders'][$order_id] ?? null;
    }

    function wc_get_orders(array $args): array
    {
        $GLOBALS['wperh_test_order_query'] = $args;
        return array();
    }

    function sanitize_key(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9_\\-]/', '', $value));
    }

    function absint($value): int
    {
        return abs((int) $value);
    }

    function get_option(string $key, $default = false)
    {
        return $GLOBALS['wperh_test_options'][$key] ?? $default;
    }

    function add_option(string $key, $value, string $deprecated = '', $autoload = true): bool
    {
        if (array_key_exists($key, $GLOBALS['wperh_test_options'])) {
            return false;
        }

        $GLOBALS['wperh_test_options'][$key] = $value;
        return true;
    }

    function update_option(string $key, $value, $autoload = null): bool
    {
        $GLOBALS['wperh_test_options'][$key] = $value;
        return true;
    }

    function delete_option(string $key): bool
    {
        unset($GLOBALS['wperh_test_options'][$key]);
        return true;
    }

    function fail_test(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    require dirname(__DIR__) . '/includes/Service/Detector.php';

    $repository = new \\WPEssential\\OrderRescueHub\\Repository\\CaseRepository();
    $detector = new \\WPEssential\\OrderRescueHub\\Service\\Detector($repository);
    $shipping_item = new DetectorSmokeItem(new DetectorSmokeProduct(true));

    foreach (array('completed', 'cancelled', 'refunded') as $index => $status) {
        $order_id = 100 + $index;
        $GLOBALS['wperh_test_orders'][$order_id] = new WC_Order($status, array($shipping_item));
        $detector->evaluate($order_id);
    }

    if (count($repository->detected) !== 0 || $repository->resolved !== array(100, 101, 102)) {
        fail_test('Terminal order statuses must not create cases and should clear any existing open case.');
    }

    $GLOBALS['wperh_test_orders'][200] = new WC_Order('on-hold', array($shipping_item));
    $detector->evaluate(200);
    if (1 !== count($repository->detected) || !in_array('missing_shipping', $repository->detected[0][1], true)) {
        fail_test('On-hold orders with missing shipping details must be detected.');
    }

    $repository->detected = array();
    $GLOBALS['wperh_test_orders'][201] = new WC_Order('processing', array(new DetectorSmokeItem(null)));
    $detector->evaluate(201);
    if (count($repository->detected) !== 0 || !in_array(201, $repository->resolved, true)) {
        fail_test('A deleted or unavailable product must not be misclassified as a stock discrepancy.');
    }

    $detector->sweep();
    if (!in_array('on-hold', $GLOBALS['wperh_test_order_query']['status'] ?? array(), true)) {
        fail_test('The scheduled sweep must include on-hold orders.');
    }

    fwrite(STDOUT, "Detector smoke checks passed.\\n");
}
