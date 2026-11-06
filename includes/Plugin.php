<?php

namespace WPEssential\OrderRescueHub;

use WPEssential\OrderRescueHub\Admin\Screen;
use WPEssential\OrderRescueHub\Admin\Assets;
use WPEssential\OrderRescueHub\Database\Installer;
use WPEssential\OrderRescueHub\Repository\CaseRepository;
use WPEssential\OrderRescueHub\Rest\CasesController;
use WPEssential\OrderRescueHub\Service\CaseService;
use WPEssential\OrderRescueHub\Service\Detector;

final class Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        load_plugin_textdomain(
            'wpessential-order-rescue-hub',
            false,
            dirname(plugin_basename(WPERH_FILE)) . '/languages'
        );

        if (!$this->woocommerce_ready()) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        $repository = new CaseRepository();
        $service = new CaseService($repository);

        (new Screen())->register();
        (new Assets())->register();
        (new CasesController($repository, $service))->register();
        (new Detector($repository))->register();

        add_action('admin_init', array(Installer::class, 'maybe_upgrade'));
    }

    private function woocommerce_ready(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_get_order');
    }

    public function dependency_notice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__(
            'WPEssential Order Rescue Hub requires an active, supported WooCommerce installation. Order monitoring is paused until WooCommerce is available.',
            'wpessential-order-rescue-hub'
        );
        echo '</p></div>';
    }
}
