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


require dirname(__DIR__) . '/includes/Repository/CaseRepository.php';
require dirname(__DIR__) . '/includes/Service/CaseService.php';
require dirname(__DIR__) . '/includes/Rest/CasesController.php';

$repository = new \WPEssential\OrderRescueHub\Repository\CaseRepository();
$case_service = new \WPEssential\OrderRescueHub\Service\CaseService($repository);
$controller = new \WPEssential\OrderRescueHub\Rest\CasesController($repository, $case_service);
$admin = get_role('administrator');
if ($admin) {
    $admin->add_cap('manage_woocommerce');
}
$subscriber_id = wp_create_user('wperh-readonly-test', 'temporary-subscriber-password', 'readonly@example.invalid');
if (is_wp_error($subscriber_id)) {
    fwrite(STDERR, "Could not create the isolated subscriber security fixture.\n");
    exit(1);
}
wp_set_current_user((int) $subscriber_id);
if ($controller->can_manage()) {
    fwrite(STDERR, "A subscriber unexpectedly passed the WooCommerce manager capability check.\n");
    exit(1);
}
$fake_repository = new class extends \WPEssential\OrderRescueHub\Repository\CaseRepository {
    public function find(int $case_id): ?array
    {
        return array('case_id' => $case_id, 'order_id' => 424242, 'state' => 'open', 'row_version' => 1);
    }

    public function events(int $case_id, int $limit = 100): array
    {
        return array();
    }
};
$guarded = new \WPEssential\OrderRescueHub\Rest\CasesController(
    $fake_repository,
    new \WPEssential\OrderRescueHub\Service\CaseService($fake_repository)
);
$request = new WP_REST_Request('GET', '/wperh/v1/cases/1');
$request->set_url_params(array('id' => 1));
$denied_detail = $guarded->get_case($request);
if (!is_wp_error($denied_detail) || 403 !== $denied_detail->get_error_data()['status']) {
    fwrite(STDERR, "A subscriber was not denied access to the case detail for an inaccessible order.\n");
    exit(1);
}
$request = new WP_REST_Request('POST', '/wperh/v1/cases/1/transition');
$request->set_url_params(array('id' => 1));
$request->set_param('state', 'closed');
$denied_transition = $guarded->transition($request);
if (!is_wp_error($denied_transition) || 403 !== $denied_transition->get_error_data()['status']) {
    fwrite(STDERR, "A subscriber was not denied the case transition mutation.\n");
    exit(1);
}
wp_set_current_user(0);
fwrite(STDOUT, "WordPress subscriber list/detail/mutation permission checks passed.\n");

$fixture_count = 10000;
$chunk_size = 250;
for ($offset = 0; $offset < $fixture_count; $offset += $chunk_size) {
    $tuples = array();
    $end = min($fixture_count, $offset + $chunk_size);
    for ($i = $offset; $i < $end; $i++) {
        $order_id = 1000000 + $i;
        $reason = 0 === $i % 2 ? '["pending_payment"]' : '["missing_shipping"]';
        $priority = 0 === $i % 2 ? 20 : 30;
        $detected_at = gmdate('Y-m-d H:i:s', time() - (($i % 168) * HOUR_IN_SECONDS));
        $tuples[] = $wpdb->prepare('(%d, %s, %s, %s, %d, %s, %s, %s, %s)', $order_id, 'open', $reason, '{}', $priority, $detected_at, $detected_at, $detected_at, $detected_at);
    }
    $wpdb->query("INSERT INTO {$cases} (order_id, state, reason_codes, evidence, priority, first_detected_at, last_detected_at, created_at, updated_at) VALUES " . implode(',', $tuples));
    if ($wpdb->last_error) {
        fwrite(STDERR, "Could not seed the synthetic inbox performance fixture: " . $wpdb->last_error . "\n");
        exit(1);
    }
}
$started_at = microtime(true);
$page = $repository->list(array('state' => 'all', 'reason' => 'pending_payment', 'page' => 1, 'per_page' => 25));
$elapsed_ms = (microtime(true) - $started_at) * 1000;
if (5000 !== $page['total'] || 25 !== count($page['items']) || 200 !== $page['total_pages']) {
    fwrite(STDERR, "The 10,000-case synthetic filter/pagination result was incorrect.\n");
    exit(1);
}
if ($elapsed_ms > 2000) {
    fwrite(STDERR, sprintf("The synthetic 10,000-case inbox query exceeded the 2,000ms CI guard (%.1fms).\n", $elapsed_ms));
    exit(1);
}
fwrite(STDOUT, sprintf("Synthetic 10,000-case filter and pagination check passed in %.1fms (CI-only baseline).\n", $elapsed_ms));

fwrite(STDOUT, "WordPress/MySQL 1.0.0-to-1.1.0 migration and repeat-run checks passed.\n");
