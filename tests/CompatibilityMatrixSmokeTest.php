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
do_action('init');
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
foreach (array('assignee_state', 'sla_due_state') as $index) {
    if (false === $wpdb->query("ALTER TABLE {$cases_table} DROP INDEX {$index}")) {
        fwrite(STDERR, 'Could not recreate the 1.0.0 schema (drop index ' . $index . '): ' . $wpdb->last_error . "\n");
        exit(1);
    }
}
foreach (array('assignee_id', 'sla_response_due_at', 'sla_response_met_at', 'sla_resolution_due_at', 'sla_state', 'sla_paused_at') as $column) {
    if (false === $wpdb->query("ALTER TABLE {$cases_table} DROP COLUMN {$column}")) {
        fwrite(STDERR, 'Could not recreate the 1.0.0 schema (drop ' . $column . '): ' . $wpdb->last_error . "\n");
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
$case = $repository->find_by_order($order_id);
if (!$case) {
    $case = $repository->detect($order_id, array('missing_shipping'), array('status' => 'pending', 'shipping_missing' => true));
}
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
$second_order = wc_create_order(array('status' => 'pending'));
if (is_wp_error($second_order) || !($second_order instanceof WC_Order)) {
    fwrite(STDERR, "Could not create the second disposable order for access isolation.\n");
    exit(1);
}
$second_order->add_product(wc_get_product($product_id), 1);
$second_order->calculate_totals();
$second_order->save();
$second_order_id = (int) $second_order->get_id();
$second_case = $repository->find_by_order($second_order_id);
if (!$second_case) {
    $second_case = $repository->detect($second_order_id, array('missing_shipping'), array('status' => 'pending', 'shipping_missing' => true));
}
if (!$second_case) {
    fwrite(STDERR, "The second order did not produce a case for access isolation.\n");
    exit(1);
}
$scope_user_id = wp_create_user('wperh-order-scope', 'temporary-scope-password', 'scope@example.invalid');
if (is_wp_error($scope_user_id)) {
    fwrite(STDERR, "Could not create the isolated order-scope user.\n");
    exit(1);
}
wp_update_user(array('ID' => (int) $scope_user_id, 'role' => 'subscriber'));
wp_set_current_user((int) $scope_user_id);
$allowed_order_id = $order_id;
$capability_trace = array();
$scope_capability = static function (array $caps, string $cap, int $user_id, array $args) use ($allowed_order_id, &$capability_trace): array {
    if (in_array($cap, array('edit_shop_order', 'edit_post'), true)) {
        $capability_trace[] = array('cap' => $cap, 'args' => $args);
        return isset($args[0]) && $allowed_order_id === (int) $args[0] ? array('read') : array('do_not_allow');
    }
    return $caps;
};
add_filter('map_meta_cap', $scope_capability, 99, 4);
$allowed_request = new WP_REST_Request('GET', '/wperh/v1/cases/' . (int) $case['case_id']);
$allowed_request->set_url_params(array('id' => (int) $case['case_id']));
$allowed_scoped_detail = $controller->get_case($allowed_request);
$denied_request = new WP_REST_Request('GET', '/wperh/v1/cases/' . (int) $second_case['case_id']);
$denied_request->set_url_params(array('id' => (int) $second_case['case_id']));
$denied_scoped_detail = $controller->get_case($denied_request);
remove_filter('map_meta_cap', $scope_capability, 99);
if (!($allowed_scoped_detail instanceof WP_REST_Response)
    || !is_wp_error($denied_scoped_detail) || 403 !== $denied_scoped_detail->get_error_data()['status']
) {
    fwrite(STDERR, "Order-specific access failed (allowed=" . (is_wp_error($allowed_scoped_detail) ? $allowed_scoped_detail->get_error_code() : get_debug_type($allowed_scoped_detail)) . ", denied=" . (is_wp_error($denied_scoped_detail) ? $denied_scoped_detail->get_error_code() : get_debug_type($denied_scoped_detail)) . ", traced=" . wp_json_encode($capability_trace) . ").\n");
    exit(1);
}
wp_set_current_user(0);

// Exercise the same WordPress REST authentication filter used by cookie-auth
// requests. rest_do_request() dispatches routes directly and does not run this filter.
wp_set_current_user((int) $test_admin->ID);
global $wp_rest_auth_cookie;
$previous_rest_auth_cookie = $wp_rest_auth_cookie ?? null;
// WordPress sets this marker after validating the logged-in auth cookie; the
// isolated fixture calls the authentication filter without an HTTP server.
$wp_rest_auth_cookie = true;
$previous_nonce_header = $_SERVER['HTTP_X_WP_NONCE'] ?? null;
$_SERVER['HTTP_X_WP_NONCE'] = 'invalid-wperh-rest-nonce';
$invalid_nonce_result = apply_filters('rest_authentication_errors', null);
if (!is_wp_error($invalid_nonce_result) || 'rest_cookie_invalid_nonce' !== $invalid_nonce_result->get_error_code()
    || 403 !== $invalid_nonce_result->get_error_data()['status']
) {
    fwrite(STDERR, "WordPress REST authentication did not reject the invalid nonce with HTTP 403.\n");
    exit(1);
}
$_SERVER['HTTP_X_WP_NONCE'] = wp_create_nonce('wp_rest');
$valid_nonce_result = apply_filters('rest_authentication_errors', null);
if (true !== $valid_nonce_result) {
    fwrite(STDERR, "WordPress REST authentication did not accept a valid nonce.\n");
    exit(1);
}
if (null === $previous_nonce_header) {
    unset($_SERVER['HTTP_X_WP_NONCE']);
} else {
    $_SERVER['HTTP_X_WP_NONCE'] = $previous_nonce_header;
}
if (null === $previous_rest_auth_cookie) {
    unset($wp_rest_auth_cookie);
} else {
    $wp_rest_auth_cookie = $previous_rest_auth_cookie;
}
if (null === $previous_logged_in_cookie) {
    unset($_COOKIE[LOGGED_IN_COOKIE]);
} else {
    $_COOKIE[LOGGED_IN_COOKIE] = $previous_logged_in_cookie;
}
wp_set_current_user(0);

$wordpress_version = get_bloginfo('version');
$woocommerce_version = defined('WC_VERSION') ? WC_VERSION : 'unknown';
fwrite(STDOUT, sprintf(
    "Compatibility smoke passed: WordPress %s, WooCommerce %s, activation, 1.0.0-to-1.1.0 upgrade, populated order detail, cross-order capability isolation, admin callback, REST permission boundary, and invalid/valid WordPress REST authentication-filter nonce behavior.\n",
    $wordpress_version,
    $woocommerce_version
));
