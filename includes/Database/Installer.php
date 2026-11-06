<?php

namespace WPEssential\OrderRescueHub\Database;

final class Installer
{
    public static function install(): void
    {
        self::upgrade_schema();
        update_option('wperh_schema_version', '1.1.0', false);
    }

    public static function maybe_upgrade(): void
    {
        if ('1.0.0' !== get_option('wperh_schema_version')) {
            self::upgrade_schema();
            update_option('wperh_schema_version', '1.0.0', false);
        }
    }

    private static function upgrade_schema(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $cases = $wpdb->prefix . 'wperh_cases';
        $events = $wpdb->prefix . 'wperh_case_events';

        $cases_sql = "CREATE TABLE {$cases} (
            case_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            state varchar(24) NOT NULL DEFAULT 'open',
            reason_codes longtext NOT NULL,
            evidence longtext NOT NULL,
            priority smallint(5) unsigned NOT NULL DEFAULT 50,
            first_detected_at datetime NOT NULL,
            last_detected_at datetime NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            closed_at datetime NULL,
            outcome varchar(32) NOT NULL DEFAULT '',
            episode int(10) unsigned NOT NULL DEFAULT 1,
            row_version int(10) unsigned NOT NULL DEFAULT 1,
            assignee_id bigint(20) unsigned NULL,
            sla_response_due_at datetime NULL,
            sla_response_met_at datetime NULL,
            sla_resolution_due_at datetime NULL,
            sla_state varchar(16) NOT NULL DEFAULT '',
            sla_paused_at datetime NULL,
            PRIMARY KEY  (case_id),
            UNIQUE KEY order_id (order_id),
            KEY state_updated (state, updated_at),
            KEY priority_age (priority, first_detected_at),
            KEY assignee_state (assignee_id, state),
            KEY sla_due_state (sla_state, sla_resolution_due_at)
        ) {$charset_collate};";

        $events_sql = "CREATE TABLE {$events} (
            event_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            case_id bigint(20) unsigned NOT NULL,
            idempotency_key char(64) NOT NULL,
            event_type varchar(32) NOT NULL,
            actor_id bigint(20) unsigned NULL,
            occurred_at datetime NOT NULL,
            payload longtext NOT NULL,
            PRIMARY KEY  (event_id),
            UNIQUE KEY idempotency_key (idempotency_key),
            KEY case_occurred (case_id, occurred_at)
        ) {$charset_collate};";

        dbDelta($cases_sql);
        dbDelta($events_sql);
    }
}
