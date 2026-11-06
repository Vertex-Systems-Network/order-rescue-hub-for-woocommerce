<?php

namespace WPEssential\OrderRescueHub\Admin;

final class Screen
{
    public function register(): void
    {
        add_action('admin_menu', array($this, 'add_menu'));
    }

    public function add_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Order Rescue Hub', 'wpessential-order-rescue-hub'),
            __('Order Rescue Hub', 'wpessential-order-rescue-hub'),
            'manage_woocommerce',
            'wperh-order-rescue-hub',
            array($this, 'render')
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'wpessential-order-rescue-hub'));
        }

        echo '<div class="wrap wperh-wrap">';
        echo '<h1>' . esc_html__('Order Rescue Hub', 'wpessential-order-rescue-hub') . '</h1>';
        echo '<div id="wperh-app" aria-live="polite"></div>';
        echo '</div>';
    }
}
