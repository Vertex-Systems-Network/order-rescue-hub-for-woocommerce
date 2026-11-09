<?php

namespace {
    function sanitize_key(string $key): string
    {
        return preg_replace('/[^a-z0-9_\\-]/', '', strtolower($key));
    }

    function sanitize_text_field(string $value): string
    {
        return trim(strip_tags($value));
    }

    function absint($value): int
    {
        return abs((int) $value);
    }

    function wp_generate_uuid4(): string
    {
        return 'test-generated-id';
    }

    function fail_pro_settings_test(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

namespace {
    require dirname(__DIR__) . '/includes/Pro/Settings.php';

    $triggers = array('case.opened', 'case.age_check', 'sla.warning', 'sla.breached');
    $rules = array_map(static function (string $trigger): array {
        return array(
            'id' => str_replace('.', '-', $trigger),
            'name' => 'Test ' . $trigger,
            'enabled' => true,
            'dry_run' => true,
            'trigger' => $trigger,
            'action' => 'admin_alert',
            'value' => '',
        );
    }, $triggers);

    $clean = \WPEssential\OrderRescueHub\Pro\Settings::sanitize_rules($rules);
    if (count($clean) !== count($triggers)) {
        fail_pro_settings_test('All supported dotted Pro workflow triggers must survive settings sanitization.');
    }
    foreach ($triggers as $index => $trigger) {
        if ($clean[$index]['trigger'] !== $trigger) {
            fail_pro_settings_test('Workflow trigger identifiers must retain their exact allowlisted value.');
        }
    }

    $invalid = $rules[0];
    $invalid['trigger'] = 'case.evil';
    if (\WPEssential\OrderRescueHub\Pro\Settings::sanitize_rules(array($invalid))) {
        fail_pro_settings_test('Unrecognized workflow trigger values must be rejected.');
    }

    fwrite(STDOUT, 'Pro workflow trigger sanitization checks passed.' . PHP_EOL);
}
