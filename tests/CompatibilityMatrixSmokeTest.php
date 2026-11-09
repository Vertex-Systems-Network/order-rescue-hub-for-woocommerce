<?php

/**
 * Isolated WordPress/WooCommerce compatibility smoke test.
 *
 * Executed by the GitHub Actions L-1 version matrix against disposable MySQL
 * services. This is not a merchant-facing command or a full browser test.
 */

$wordpress_root = rtrim((string) getenv('WPERH_WORDPRESS_ROOT'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$plugin_file = (string) getenv('WPERH_PLUGIN_FILE');
if (!is_file($wordpress_root . 'wp-config-sample.php') || !is_file($plugin_file)) {
    fwrite(STDERR, "WordPress or plugin test root is missing.\n");
    exit(1);
}

$config = file_get_contents($wordpress_root . 'wp-config-sample.php');
if (false === $config) {
    fwrite(STDERR, "Could not read the WordPress sample configuration.\n");
    exit(1);
}

$replacements = array(
    'database_name_here' => (string) getenv('WPERH_DB_NAME'),
    'username_here' => (string) getenv('WPERH_DB_USER'),
    'password_here' => (string) getenv('WPERH_DB_PASSWORD'),
    'localhost' => (string) getenv('WPERH_DB_HOST'),
    'put your unique phrase here' => 'wperh-compatibility-matrix-key',
);
$config = str_replace(array_keys($replacements), array_values($replacements), $config);
$config = preg_replace("/define\( 'DB_CHARSET', 'utf8' \);/", "define( 'DB_CHARSET', 'utf8mb4' );", $config);
if (false === file_put_contents($wordpress_root . 'wp-config.php', $config)) {
    fwrite(STDERR, "Could not write the temporary WordPress configuration.\n");
    exit(1);
}

define('WP_INSTALLING', true);
define('WP_ADMIN', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require $wordpress_root . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

wp_install('WPERH compatibility matrix', 'integration-admin', 'integration@example.invalid', 0, '', 'temporary-test-password', 'en_US');
wp_set_current_user(1);

$woocommerce_plugin = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
if (!is_file($woocommerce_plugin)) {
    fwrite(STDERR, "WooCommerce package was not installed in the isolated test site.\n");
    exit(1);
}

$woocommerce_result = activate_plugin('woocommerce/woocommerce.php');
if (is_wp_error($woocommerce_result)) {
    fwrite(STDERR, 'WooCommerce activation failed: ' . $woocommerce_result->get_error_message() . "\n");
    exit(1);
}

$plugin_root = dirname($plugin_file);
$plugin_slug = basename($plugin_root);
$plugin_relative = $plugin_slug . '/' . basename($plugin_file);
$plugin_result = activate_plugin($plugin_relative);
if (is_wp_error($plugin_result)) {
    fwrite(STDERR, 'Order Rescue Hub activation failed: ' . $plugin_result->get_error_message() . "\n");
    exit(1);
}

// Plugin activation loads files after WordPress' first plugins_loaded event.
// Dispatch it once in this isolated process so the activated test plugins boot.
do_action('plugins_loaded');
if (!class_exists('WooCommerce') || !function_exists('wc_get_order')) {
    fwrite(STDERR, "WooCommerce did not initialize its order CRUD API.\n");
    exit(1);
}

if ('1.1.0' !== get_option('wperh_schema_version')) {
    fwrite(STDERR, "Order Rescue Hub activation did not install schema version 1.1.0.\n");
    exit(1);
}

// Recreate the prior 1.0.0 schema, then run the same upgrade path on every
// claimed WordPress/WooCommerce combination.
global $wpdb;
$cases_table = $wpdb->prefix . 'wperh_cases';
foreach (array('assignee_id', 'sla_response_due_at', 'sla_response_met_at', 'sla_resolution_due_at', 'sla_state', 'sla_paused_at') as $column) {
    if (false === $wpdb->query("ALTER TABLE {$cases_table} DROP COLUMN {$column}")) {
        fwrite(STDERR, 'Could not recreate the 1.0.0 schema (drop ' . $column . '): ' . $wpdb->last_error . "\n");
        exit(1);
    }
}
foreach (array('assignee_state', 'sla_due_state') as $index) {
    if (false === $wpdb->query("ALTER TABLE {$cases_table} DROP INDEX {$index}")) {
        fwrite(STDERR, 'Could not recreate the 1.0.0 schema (drop index ' . $index . '): ' . $wpdb->last_error . "\n");
        exit(1);
    }
}
update_option('wperh_schema_version', '1.0.0', false);
\WPEssential\OrderRescueHub\Database\Installer::maybe_upgrade();
if ('1.1.0' !== get_option('wperh_schema_version')) {
    fwrite(STDERR, "The 1.0.0-to-1.1.0 upgrade did not persist the current schema version.\n");
    exit(1);
}
$upgraded_columns = $wpdb->get_col("SHOW COLUMNS FROM {$cases_table}", 0);
$required_columns = array('assignee_id', 'sla_response_due_at', 'sla_response_met_at', 'sla_resolution_due_at', 'sla_state', 'sla_paused_at');
if (array_diff($required_columns, $upgraded_columns)) {
    fwrite(STDERR, "The compatibility-matrix upgrade did not restore every 1.1.0 column.\n");
    exit(1);
}
$upgraded_indexes = $wpdb->get_results("SHOW INDEX FROM {$cases_table}", ARRAY_A);
$index_names = array_unique(array_column($upgraded_indexes, 'Key_name'));
if (array_diff(array('assignee_state', 'sla_due_state'), $index_names)) {
    fwrite(STDERR, "The compatibility-matrix upgrade did not restore every 1.1.0 index.\n");
    exit(1);
}

global $wp_filter;
$admin_menu_callbacks = $wp_filter['admin_menu']->callbacks ?? array();
$hub_menu_registered = false;
foreach ($admin_menu_callbacks as $priority_callbacks) {
    foreach ($priority_callbacks as $callback) {
        $callable = $callback['function'] ?? null;
        if (is_array($callable) && isset($callable[0], $callable[1])
            && $callable[0] instanceof \WPEssential\OrderRescueHub\Admin\Screen
            && 'add_menu' === $callable[1]
        ) {
            $hub_menu_registered = true;
            break 2;
        }
    }
}
if (!$hub_menu_registered) {
    fwrite(STDERR, "Order Rescue Hub did not attach its WooCommerce admin menu callback.\n");
    exit(1);
}

do_action('rest_api_init', rest_get_server());
$routes = rest_get_server()->get_routes();
if (!isset($routes['/wperh/v1/cases'])) {
    fwrite(STDERR, "Order Rescue Hub did not register its case-list REST route.\n");
    exit(1);
}

// Plugin activation runs in WordPress' plugin sandbox. Verify the case-list
// route denies requests after the sandbox has cleared the current user.
wp_set_current_user(0);
$response = rest_do_request(new WP_REST_Request('GET', '/wperh/v1/cases'));
if (!in_array($response->get_status(), array(401, 403), true)) {
    fwrite(STDERR, 'An unauthenticated case-list request returned HTTP ' . $response->get_status() . "; expected 401 or 403.\n");
    exit(1);
}

// Create a disposable WooCommerce order through CRUD, open a Hub exception,
// and verify that the populated case-detail controller resolves the order.
$administrator_role = get_role('administrator');
if ($administrator_role) {
    $administrator_role->add_cap('manage_woocommerce');
}
$test_admin = get_user_by('login', 'integration-admin');
if (!$test_admin) {
    fwrite(STDERR, "The compatibility fixture administrator could not be loaded for detail verification.\n");
    exit(1);
}
wp_set_current_user((int) $test_admin->ID);
if (!current_user_can('manage_woocommerce')) {
    fwrite(STDERR, "The compatibility fixture administrator lacks manage_woocommerce for detail verification.\n");
    exit(1);
}
$product = new WC_Product_Simple();
$product->set_name('WPERH Compatibility Fixture');
$product->set_regular_price('12.00');
$product_id = $product->save();
if (!$product_id) {
    fwrite(STDERR, "Could not create the disposable WooCommerce product fixture.\n");
    exit(1);
}
$order = wc_create_order(array('status' => 'pending'));
if (is_wp_error($order) || !($order instanceof WC_Order)) {
    fwrite(STDERR, "Could not create the disposable WooCommerce order fixture.\n");
    exit(1);
}
$order->add_product(wc_get_product($product_id), 1);
$order->calculate_totals();
$order->save();
$order_id = (int) $order->get_id();
$repository = new \WPEssential\OrderRescueHub\Repository\CaseRepository();
$service = new \WPEssential\OrderRescueHub\Service\CaseService($repository);
$controller = new \WPEssential\OrderRescueHub\Rest\CasesController($repository, $service);
$case = $repository->detect($order_id, array('missing_shipping'), array('status' => 'pending'));
if (!$case) {
    fwrite(STDERR, "The populated WooCommerce order did not create a Hub exception.\n");
    exit(1);
}
$detail_request = new WP_REST_Request('GET', '/wperh/v1/cases/' . (int) $case['case_id']);
$detail_request->set_url_params(array('id' => (int) $case['case_id']));
$detail = $controller->get_case($detail_request);
$detail_data = $detail instanceof WP_REST_Response ? $detail->get_data() : array();
$order_snapshot = $detail_data['order'] ?? array();
$fixture_item_found = false;
foreach (($order_snapshot['items'] ?? array()) as $item) {
    if ('WPERH Compatibility Fixture' === ($item['name'] ?? '')) {
        $fixture_item_found = true;
        break;
    }
}
if (!($detail instanceof WP_REST_Response) || 'pending' !== ($order_snapshot['status'] ?? '')
    || true !== ($order_snapshot['shipping_missing'] ?? false) || !$fixture_item_found
) {
    fwrite(STDERR, "Populated case detail did not include the expected WooCommerce order snapshot.\n");
    exit(1);
}
wp_set_current_user(0);

$wordpress_version = get_bloginfo('version');
$woocommerce_version = defined('WC_VERSION') ? WC_VERSION : 'unknown';
fwrite(STDOUT, sprintf(
    "Compatibility smoke passed: WordPress %s, WooCommerce %s, activation, 1.0.0-to-1.1.0 upgrade, populated order detail, admin callback, and REST permission boundary.\n",
    $wordpress_version,
    $woocommerce_version
));
