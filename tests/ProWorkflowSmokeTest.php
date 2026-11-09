<?php

namespace WPEssential\OrderRescueHub\Repository {
    final class CaseRepository
    {
        public array $case = array(
            'case_id' => 17,
            'order_id' => 85,
            'state' => 'triaged',
            'reason_codes' => array('pending_payment'),
            'first_detected_at' => '2026-11-09 00:00:00',
            'episode' => 1,
            'row_version' => 2,
        );
        public array $events = array();
        private array $keys = array();

        public function find(int $case_id): ?array
        {
            return $case_id === $this->case['case_id'] ? $this->case : null;
        }

        public function has_event(string $key): bool
        {
            return isset($this->keys[$key]);
        }

        public function record_event(int $case_id, string $event_type, array $payload, ?int $actor_id, string $idempotency_key): bool
        {
            if ($this->has_event($idempotency_key)) {
                return false;
            }
            $this->keys[$idempotency_key] = true;
            $this->events[] = array('type' => $event_type, 'payload' => $payload);
            return true;
        }
    }
}

namespace WPEssential\OrderRescueHub\Service {
    final class CaseService
    {
    }
}

namespace WPEssential\OrderRescueHub\Pro {
    final class AssignmentService
    {
    }

    final class Settings
    {
        public static array $rules = array();

        public static function get(): array
        {
            return array('rules' => self::$rules);
        }
    }
}

namespace {
    if (!defined('HOUR_IN_SECONDS')) {
        define('HOUR_IN_SECONDS', 3600);
    }

    function absint($value): int
    {
        return abs((int) $value);
    }

    function fail_pro_workflow_test(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    require dirname(__DIR__) . '/includes/Pro/WorkflowEngine.php';

    \WPEssential\OrderRescueHub\Pro\Settings::$rules = array(
        array(
            'id' => 'state-rule',
            'version' => 1,
            'enabled' => true,
            'dry_run' => true,
            'trigger' => 'case.state_changed',
            'case_state' => '',
            'reason' => '',
            'min_age_hours' => 0,
            'action' => 'admin_alert',
        ),
        array(
            'id' => 'reason-rule',
            'version' => 1,
            'enabled' => true,
            'dry_run' => true,
            'trigger' => 'case.reason_changed',
            'case_state' => '',
            'reason' => '',
            'min_age_hours' => 0,
            'action' => 'admin_alert',
        ),
    );

    $repository = new \WPEssential\OrderRescueHub\Repository\CaseRepository();
    $service = new \WPEssential\OrderRescueHub\Service\CaseService();
    $assignment = new \WPEssential\OrderRescueHub\Pro\AssignmentService();
    $engine = new \WPEssential\OrderRescueHub\Pro\WorkflowEngine($repository, $service, $assignment);

    $engine->on_case_transitioned(17, 'triaged');
    if (count($repository->events) !== 1 || 'state-rule' !== $repository->events[0]['payload']['rule_id']) {
        fail_pro_workflow_test('A case state change must run its matching Pro workflow preview.');
    }
    $engine->on_case_transitioned(17, 'waiting');
    if (count($repository->events) !== 1) {
        fail_pro_workflow_test('A stale state-change event must not run a workflow against a different current state.');
    }
    $engine->on_case_transitioned(17, 'triaged');
    if (count($repository->events) !== 1) {
        fail_pro_workflow_test('Duplicate state-change callbacks must be idempotent for the case episode.');
    }

    $engine->on_case_reasons_changed($repository->case);
    if (count($repository->events) !== 2 || 'reason-rule' !== $repository->events[1]['payload']['rule_id']) {
        fail_pro_workflow_test('A case reason change must run its matching Pro workflow preview.');
    }

    fwrite(STDOUT, 'Pro case-change workflow checks passed.' . PHP_EOL);
}
