<?php

$GLOBALS['wperh_installer_test_options'] = array();
$GLOBALS['wperh_installer_test_dbdelta_calls'] = array();

$fixture_root = sys_get_temp_dir() . '/wperh-installer-smoke-' . getmypid() . '/';
$upgrade_file = $fixture_root . 'wp-admin/includes/upgrade.php';
if (!is_dir(dirname($upgrade_file)) && !mkdir(dirname($upgrade_file), 0777, true) && !is_dir(dirname($upgrade_file))) {
    fwrite(STDERR, "Could not create WordPress upgrade fixture directory.\n");
    exit(1);
}
file_put_contents($upgrade_file, "<?php\n");
define('ABSPATH', $fixture_root);

register_shutdown_function(static function () use ($upgrade_file, $fixture_root): void {
    if (is_file($upgrade_file)) {
        unlink($upgrade_file);
    }
    $include_dir = dirname($upgrade_file);
    $admin_dir = dirname($include_dir);
    if (is_dir($include_dir)) {
        rmdir($include_dir);
    }
    if (is_dir($admin_dir)) {
        rmdir($admin_dir);
    }
    if (is_dir($fixture_root)) {
        rmdir($fixture_root);
    }
});

final class InstallerSmokeWpdb
{
    public string $prefix = 'wp_';

    public function get_charset_collate(): string
    {
        return 'DEFAULT CHARACTER SET utf8mb4';
    }
}

$wpdb = new InstallerSmokeWpdb();

function get_option(string $key, $default = false)
{
    return $GLOBALS['wperh_installer_test_options'][$key] ?? $default;
}

function update_option(string $key, $value, $autoload = null): bool
{
    $GLOBALS['wperh_installer_test_options'][$key] = $value;
    return true;
}

function dbDelta(string $sql): array
{
    $GLOBALS['wperh_installer_test_dbdelta_calls'][] = $sql;
    return array();
}

function fail_installer_test(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

require dirname(__DIR__) . '/includes/Database/Installer.php';

\WPEssential\OrderRescueHub\Database\Installer::maybe_upgrade();
if (2 !== count($GLOBALS['wperh_installer_test_dbdelta_calls'])) {
    fail_installer_test('An older schema must install both Hub tables.');
}
if ('1.1.0' !== get_option('wperh_schema_version')) {
    fail_installer_test('A successful schema upgrade must persist version 1.1.0.');
}

\WPEssential\OrderRescueHub\Database\Installer::maybe_upgrade();
if (2 !== count($GLOBALS['wperh_installer_test_dbdelta_calls'])) {
    fail_installer_test('A current schema must not rerun dbDelta on every admin request.');
}

$GLOBALS['wperh_installer_test_options']['wperh_schema_version'] = '1.1.0';
\WPEssential\OrderRescueHub\Database\Installer::maybe_upgrade();
if (2 !== count($GLOBALS['wperh_installer_test_dbdelta_calls'])) {
    fail_installer_test('A current-version schema must remain unchanged.');
}

fwrite(STDOUT, "Installer upgrade-gate smoke checks passed.\n");
