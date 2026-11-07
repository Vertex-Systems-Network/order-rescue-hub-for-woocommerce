<?php

/**
 * Database-backed schema upgrade test.
 *
 * Runs only in an isolated GitHub Actions MySQL service against a temporary
 * WordPress installation. It is not a merchant-facing command.
 */

$wordpress_root = rtrim((string) getenv('WPERH_WORDPRESS_ROOT'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (!is_file($wordpress_root . 'wp-config-sample.php')) {
    fwrite(STDERR, "WordPress test root is missing wp-config-sample.php.\n");
    exit(1);
}

$config = file_get_contents($wordpress_root . 'wp-config-sample.php');
if (false === $config) {
    fwrite(STDERR, "Could not read the WordPress sample configuration.\n");
    exit(1);
}

$replacements = array(
    "database_name_here" => (string) getenv('WPERH_DB_NAME'),
    "username_here" => (string) getenv('WPERH_DB_USER'),
    "password_here" => (string) getenv('WPERH_DB_PASSWORD'),
    "localhost" => (string) getenv('WPERH_DB_HOST'),
    "put your unique phrase here" => "wperh-isolated-integration-test-key",
);
$config = str_replace(array_keys($replacements), array_values($replacements), $config);
$config = preg_replace("/define\\( 'DB_CHARSET', 'utf8' \\);/", "define( 'DB_CHARSET', 'utf8mb4' );", $config);
if (false === file_put_contents($wordpress_root . 'wp-config.php', $config)) {
    fwrite(STDERR, "Could not write the temporary WordPress configuration.\n");
    exit(1);
}

define('WP_INSTALLING', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require $wordpress_root . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if (!function_exists('wp_install')) {
    fwrite(STDERR, "WordPress installer functions did not load.\n");
    exit(1);
}

wp_install('WPERH isolated schema test', 'integration@example.invalid', 0, '', 'temporary-test-password', 'en_US', 'integration-admin');

global $wpdb;
$cases = $wpdb->prefix . 'wperh_cases';
$events = $wpdb->prefix . 'wperh_case_events';
$charset = $wpdb->get_charset_collate();

$legacy_cases = "CREATE TABLE {$cases} (
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
    PRIMARY KEY  (case_id),
    UNIQUE KEY order_id (order_id),
    KEY state_updated (state, updated_at),
    KEY priority_age (priority, first_detected_at)
) {$charset};";

$legacy_events = "CREATE TABLE {$events} (
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
) {$charset};";

dbDelta($legacy_cases);
dbDelta($legacy_events);
update_option('wperh_schema_version', '1.0.0', false);

require dirname(__DIR__) . '/includes/Database/Installer.php';

\WPEssential\OrderRescueHub\Database\Installer::maybe_upgrade();
if ('1.1.0' !== get_option('wperh_schema_version')) {
    fwrite(STDERR, "The database-backed upgrade did not persist schema version 1.1.0.\n");
    exit(1);
}

$columns = $wpdb->get_col("SHOW COLUMNS FROM {$cases}", 0);
$required_columns = array('assignee_id', 'sla_response_due_at', 'sla_response_met_at', 'sla_resolution_due_at', 'sla_state', 'sla_paused_at');
$missing = array_values(array_diff($required_columns, $columns));
if ($missing) {
    fwrite(STDERR, 'The database-backed upgrade missed columns: ' . implode(', ', $missing) . "\n");
    exit(1);
}

$indexes = $wpdb->get_results("SHOW INDEX FROM {$cases}", ARRAY_A);
$index_names = array_unique(array_column($indexes, 'Key_name'));
$required_indexes = array('assignee_state', 'sla_due_state');
$missing_indexes = array_values(array_diff($required_indexes, $index_names));
if ($missing_indexes) {
    fwrite(STDERR, 'The database-backed upgrade missed indexes: ' . implode(', ', $missing_indexes) . "\n");
    exit(1);
}

\WPEssential\OrderRescueHub\Database\Installer::maybe_upgrade();
if ('1.1.0' !== get_option('wperh_schema_version')) {
    fwrite(STDERR, "A repeated upgrade changed the current schema version.\n");
    exit(1);
}
$columns_after_repeat = $wpdb->get_col("SHOW COLUMNS FROM {$cases}", 0);
if ($columns !== $columns_after_repeat) {
    fwrite(STDERR, "A repeated upgrade changed the resulting schema columns.\n");
    exit(1);
}

fwrite(STDOUT, "WordPress/MySQL 1.0.0-to-1.1.0 migration and repeat-run checks passed.\n");
