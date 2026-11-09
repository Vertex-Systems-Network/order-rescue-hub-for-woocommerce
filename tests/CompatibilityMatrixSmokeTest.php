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
$test_admin = get_userdata(1);
if ($test_admin) {
    $test_admin->add_cap('manage_woocommerce');
}

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

$response = rest_do_request(new WP_REST_Request('GET', '/wperh/v1/cases'));
if (200 !== $response->get_status()) {
    fwrite(STDERR, 'The empty case-list request returned HTTP ' . $response->get_status() . ".\n");
    exit(1);
}
$data = $response->get_data();
if (!is_array($data) || 0 !== ($data['total'] ?? null) || !isset($data['items']) || array() !== $data['items']) {
    fwrite(STDERR, "The clean-install case-list response did not return an empty, paginated result.\n");
    exit(1);
}

$wordpress_version = get_bloginfo('version');
$woocommerce_version = defined('WC_VERSION') ? WC_VERSION : 'unknown';
fwrite(STDOUT, sprintf(
    "Compatibility smoke passed: WordPress %s, WooCommerce %s, activation/schema, admin menu, and empty case-list REST flow.\n",
    $wordpress_version,
    $woocommerce_version
));
