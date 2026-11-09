<?php

namespace WPEssential\OrderRescueHub\Repository {
    final class CaseRepository
    {
        public array $events = array();
        public array $case = array(
            'case_id' => 17,
            'order_id' => 85,
            'episode' => 1,
        );

        public function find(int $case_id): ?array
        {
            return $case_id === $this->case['case_id'] ? $this->case : null;
        }

        public function has_event(string $key): bool
        {
            return false;
        }

        public function record_event(int $case_id, string $event_type, array $payload, ?int $actor_id = null, ?string $idempotency_key = null): bool
        {
            $this->events[] = array('event_type' => $event_type, 'payload' => $payload);
            return true;
        }
    }
}

namespace WPEssential\OrderRescueHub\Pro {
    final class Settings
    {
        public static function get(): array
        {
            return array(
                'alerts_enabled' => true,
                'recipients' => array('merchant@example.com'),
            );
        }
    }
}

namespace {
    $GLOBALS['wperh_test_locks'] = array();
    $GLOBALS['wperh_test_scheduled'] = array();
    $GLOBALS['wperh_test_mail_mode'] = 'throw';

    function add_option(string $name, $value = '', string $deprecated = '', $autoload = 'yes'): bool
    {
        if (array_key_exists($name, $GLOBALS['wperh_test_locks'])) {
            return false;
        }
        $GLOBALS['wperh_test_locks'][$name] = $value;
        return true;
    }

    function get_option(string $name, $default = false)
    {
        return $GLOBALS['wperh_test_locks'][$name] ?? $default;
    }

    function delete_option(string $name): bool
    {
        unset($GLOBALS['wperh_test_locks'][$name]);
        return true;
    }

    function wp_mail(array $recipients, string $subject, string $message): bool
    {
        if ('throw' === $GLOBALS['wperh_test_mail_mode']) {
            throw new \RuntimeException('Simulated mail transport failure.');
        }
        return 'sent' === $GLOBALS['wperh_test_mail_mode'];
    }

    function wp_schedule_single_event(int $timestamp, string $hook, array $args = array()): bool
    {
        $GLOBALS['wperh_test_scheduled'][] = array($timestamp, $hook, $args);
        return true;
    }

    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
    }

    function __(string $text, string $domain = ''): string
    {
        return $text;
    }

    function wp_specialchars_decode(string $text, int $quote_style = ENT_QUOTES): string
    {
        return $text;
    }

    function get_bloginfo(string $show = ''): string
    {
        return 'QA shop';
    }

    function admin_url(string $path = ''): string
    {
        return '/wp-admin/' . $path;
    }

    function esc_url_raw(string $url): string
    {
        return $url;
    }

    function sanitize_key(string $key): string
    {
        return preg_replace('/[^a-z0-9_\\-]/', '', strtolower($key));
    }

    function fail_pro_alert_test(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    require dirname(__DIR__) . '/includes/Pro/AlertService.php';

    $repository = new \WPEssential\OrderRescueHub\Repository\CaseRepository();
    $service = new \WPEssential\OrderRescueHub\Pro\AlertService($repository);
    $case = $repository->case;
    $now = time();

    $service->on_case_detected($case);
    if (count($repository->events) !== 1 || 'failed' !== $repository->events[0]['payload']['result']) {
        fail_pro_alert_test('Thrown mail transport errors must be recorded as failed alert deliveries.');
    }
    if (count($GLOBALS['wperh_test_scheduled']) !== 1 || 'wperh_retry_alert' !== $GLOBALS['wperh_test_scheduled'][0][1] || 2 !== $GLOBALS['wperh_test_scheduled'][0][2][2]) {
        fail_pro_alert_test('A thrown first-attempt delivery must schedule the bounded second attempt.');
    }
    if ($GLOBALS['wperh_test_locks']) {
        fail_pro_alert_test('The alert delivery lock must be released after an exception.');
    }

    $GLOBALS['wperh_test_mail_mode'] = 'failed';
    $service->retry_alert(17, 'case.opened', 2);
    if (count($repository->events) !== 2 || 3 !== $GLOBALS['wperh_test_scheduled'][1][2][2]) {
        fail_pro_alert_test('A false mail result on retry two must schedule only the final retry.');
    }
    $service->retry_alert(17, 'case.opened', 3);
    if (count($repository->events) !== 3 || count($GLOBALS['wperh_test_scheduled']) !== 2) {
        fail_pro_alert_test('The alert retry chain must stop after the third attempt.');
    }

    $GLOBALS['wperh_test_mail_mode'] = 'sent';
    $success_repository = new \WPEssential\OrderRescueHub\Repository\CaseRepository();
    $success_service = new \WPEssential\OrderRescueHub\Pro\AlertService($success_repository);
    $success_service->on_case_detected($success_repository->case);
    if ('sent' !== $success_repository->events[0]['payload']['result'] || count($GLOBALS['wperh_test_scheduled']) !== 2) {
        fail_pro_alert_test('Successful alert delivery must be recorded without scheduling a retry.');
    }

    fwrite(STDOUT, 'Pro alert retry checks passed.' . PHP_EOL);
}
