<?php
/**
 * Plugin Name: WPEssential Order Rescue Hub
 * Description: A focused workspace for WooCommerce order exceptions, private resolution notes, and case history.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * Text Domain: wpessential-order-rescue-hub
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPERH_VERSION', '0.1.0');
define('WPERH_FILE', __FILE__);
define('WPERH_PATH', plugin_dir_path(__FILE__));
define('WPERH_URL', plugin_dir_url(__FILE__));

$wperh_autoloader = WPERH_PATH . 'vendor/autoload.php';
if (is_readable($wperh_autoloader)) {
    require_once $wperh_autoloader;
} else {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error"><p>' .
            esc_html__('Order Rescue Hub could not load its Composer autoloader. Reinstall the plugin package.', 'wpessential-order-rescue-hub') .
            '</p></div>';
    });
    return;
}

register_activation_hook(__FILE__, array(\WPEssential\OrderRescueHub\Database\Installer::class, 'install'));

register_deactivation_hook(__FILE__, static function () {\n    wp_clear_scheduled_hook('wperh_exception_sweep');\n});\n\nadd_action('plugins_loaded', static function () {
    \WPEssential\OrderRescueHub\Plugin::instance()->boot();
}, 20);
