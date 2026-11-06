<?php

namespace WPEssential\OrderRescueHub\Admin;

final class Assets
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    public function enqueue(string $hook): void
    {
        if ('woocommerce_page_wperh-order-rescue-hub' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wperh-admin',
            WPERH_URL . 'assets/admin.css',
            array('wp-components'),
            WPERH_VERSION
        );

        wp_enqueue_script(
            'wperh-admin',
            WPERH_URL . 'assets/admin.js',
            array('wp-element', 'wp-api-fetch', 'wp-i18n'),
            WPERH_VERSION,
            true
        );

        wp_set_script_translations('wperh-admin', 'wpessential-order-rescue-hub', WPERH_PATH . 'languages');

        wp_localize_script('wperh-admin', 'wperhSettings', array(
            'apiRoot' => esc_url_raw(rest_url('wperh/v1/')),
            'pageSize' => 25,
            'timezone' => wp_timezone_string(),
        ));
    }
}
