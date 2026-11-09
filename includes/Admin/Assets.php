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
            WPERH_BUILD_ID
        );

        wp_enqueue_script(
            'wperh-admin',
            WPERH_URL . 'assets/admin.js',
            array('wp-element', 'wp-api-fetch', 'wp-i18n'),
            WPERH_BUILD_ID,
            true
        );

        wp_set_script_translations('wperh-admin', 'wpessential-order-rescue-hub-woocommerce', WPERH_PATH . 'languages');

        wp_localize_script('wperh-admin', 'wperhSettings', array(
            'apiRoot' => esc_url_raw(rest_url('wperh/v1/')),
            'pageSize' => 25,
            'orderEditUrlPrefix' => esc_url_raw(admin_url('admin.php?page=wc-orders&action=edit&id=')),
            'isPro' => defined('WPERH_PRO_EDITION') && true === WPERH_PRO_EDITION,
            'canManageSettings' => current_user_can('manage_woocommerce'),
            'timezone' => wp_timezone_string(),
        ));
    }
}
