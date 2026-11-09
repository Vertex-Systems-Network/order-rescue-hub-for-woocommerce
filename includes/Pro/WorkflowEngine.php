<?php

namespace WPEssential\OrderRescueHub\Pro;

use WPEssential\OrderRescueHub\Repository\CaseRepository;
use WPEssential\OrderRescueHub\Service\CaseService;

final class WorkflowEngine
{
    private CaseRepository $repository;
    private CaseService $case_service;
    private AssignmentService $assignment;

    public function __construct(CaseRepository $repository, CaseService $case_service, AssignmentService $assignment)
    {
        $this->repository = $repository;
        $this->case_service = $case_service;
        $this->assignment = $assignment;
    }

    public function register(): void
    {
        add_action('wperh_case_detected', array($this, 'on_case_detected'), 20);
        add_action('wperh_case_transitioned', array($this, 'on_case_transitioned'), 20, 2);
        add_action('wperh_case_reasons_changed', array($this, 'on_case_reasons_changed'), 20);
        add_action('wperh_sla_event', array($this, 'on_sla_event'), 20, 2);
        add_action('wperh_exception_sweep', array($this, 'on_scheduled_sweep'), 30);
        add_action('wperh_retry_workflow', array($this, 'retry_rule'), 10, 5);
    }

    public function retry_rule(int $case_id, string $rule_id, int $version, string $trigger, int $attempt): void
    {
        $case = $this->repository->find($case_id);
        if (!$case || $attempt > 3) {
            return;
        }
        $this->run_for($trigger, $case, $attempt, $rule_id, $version);
    }

    public function on_case_detected(array $case): void
    {
        $this->run_for('case.opened', $case);
    }

    public function on_scheduled_sweep(): void
    {
        $first = $this->repository->list(array('state' => 'all', 'page' => 1, 'per_page' => 100));
        $total_pages = max(1, (int) $first['total_pages']);
        $page = min(max(1, absint(get_option('wperh_workflow_sweep_page', 1))), $total_pages);
        $batches = min($total_pages, 10);

        for ($index = 0; $index < $batches; $index++) {
            $batch = 1 === $page
                ? $first
                : $this->repository->list(array('state' => 'all', 'page' => $page, 'per_page' => 100));
            foreach ($batch['items'] as $case) {
                $this->run_for('case.age_check', $case);
            }
            $page = $page >= $total_pages ? 1 : $page + 1;
        }

        update_option('wperh_workflow_sweep_page', $page, false);
    }

    public function on_sla_event(array $case, string $event): void
    {
        $trigger = 'sla.' . $event;
        if (in_array($trigger, array('sla.warning', 'sla.breached'), true)) {
            $this->run_for($trigger, $case);
        }
    }

    private function run_for(string $trigger, array $case, int $attempt = 1, string $only_rule = '', int $only_version = 0): void
    {
        $settings = Settings::get();
        foreach ($settings['rules'] as $rule) {
            if (empty($rule['enabled']) || $rule['trigger'] !== $trigger || ($only_rule && ($rule['id'] !== $only_rule || (int) $rule['version'] !== $only_version)) || !$this->matches($rule, $case)) {
                continue;
            }

            $key = hash('sha256', implode(':', array('workflow', $rule['id'], $rule['version'], $case['case_id'], $case['episode'], $trigger, $attempt)));
            if ($this->repository->has_event($key)) {
                continue;
            }

            $case = $this->repository->find((int) $case['case_id']) ?: $case;
            $dry_run = !empty($rule['dry_run']);
            if ($dry_run) {
                $this->repository->record_event((int) $case['case_id'], 'workflow_preview', array(
                    'rule_id' => $rule['id'],
                    'rule_version' => $rule['version'],
                    'trigger' => $trigger,
                    'action' => $rule['action'],
                    'result' => 'dry_run',
                ), null, $key);
                continue;
            }

            if (!$this->repository->record_event((int) $case['case_id'], 'workflow_run', array(
                'rule_id' => $rule['id'],
                'rule_version' => $rule['version'],
                'trigger' => $trigger,
                'result' => 'started',
            ), null, $key)) {
                continue;
            }

            $result = $this->execute($rule, $case);
            $this->repository->record_event((int) $case['case_id'], 'workflow_result', array(
                'rule_id' => $rule['id'],
                'rule_version' => $rule['version'],
                'trigger' => $trigger,
                'attempt' => $attempt,
                'result' => $result,
            ), null, hash('sha256', $key . ':result'));
            if (in_array($result, array('failed', 'stale'), true) && $attempt < 3) {
                wp_schedule_single_event(time() + (300 * $attempt), 'wperh_retry_workflow', array((int) $case['case_id'], $rule['id'], (int) $rule['version'], $trigger, $attempt + 1));
            }
        }
    }

    private function matches(array $rule, array $case): bool
    {
        if (!empty($rule['case_state']) && $rule['case_state'] !== $case['state']) {
            return false;
        }
        if (!empty($rule['reason']) && !in_array($rule['reason'], $case['reason_codes'], true)) {
            return false;
        }
        $age = max(0, (int) floor((time() - strtotime($case['first_detected_at'] . ' UTC')) / HOUR_IN_SECONDS));
        return $age >= (int) $rule['min_age_hours'];
    }

    private function execute(array $rule, array $case): string
    {
        switch ($rule['action']) {
            case 'set_priority':
                $priority = min(100, max(1, absint($rule['value'])));
                return $this->repository->update_case($case, array('priority' => $priority), 'workflow_priority', array('rule_id' => $rule['id'], 'priority' => $priority)) ? 'applied' : 'stale';

            case 'assign_case':
                $result = $this->assignment->assign((int) $case['case_id'], absint($rule['value']));
                return is_wp_error($result) ? 'failed' : 'applied';

            case 'set_case_state':
                $target = sanitize_key($rule['value']);
                if (!in_array($target, array('open', 'triaged', 'waiting'), true)) {
                    return 'rejected';
                }
                if ($target === $case['state']) {
                    return 'no_change';
                }
                $result = $this->case_service->transition((int) $case['case_id'], $target, '', (int) $case['row_version']);
                return is_wp_error($result) ? 'failed' : 'applied';

            case 'admin_alert':
                $settings = Settings::get();
                if (empty($settings['alerts_enabled']) || empty($settings['recipients'])) {
                    return 'skipped';
                }
                do_action('wperh_send_admin_alert', $case, 'workflow.' . $rule['id']);
                return 'queued';
        }

        return 'rejected';
    }
}
