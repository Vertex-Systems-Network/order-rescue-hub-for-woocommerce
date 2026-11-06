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
        add_action('wperh_sla_event', array($this, 'on_sla_event'), 20, 2);
    }

    public function on_case_detected(array $case): void
    {
        $this->run_for('case.opened', $case);
    }

    public function on_sla_event(array $case, string $event): void
    {
        $trigger = 'sla.' . $event;
        if (in_array($trigger, array('sla.warning', 'sla.breached'), true)) {
            $this->run_for($trigger, $case);
        }
    }

    private function run_for(string $trigger, array $case): void
    {
        $settings = Settings::get();
        foreach ($settings['rules'] as $rule) {
            if (empty($rule['enabled']) || $rule['trigger'] !== $trigger || !$this->matches($rule, $case)) {
                continue;
            }

            $key = hash('sha256', implode(':', array('workflow', $rule['id'], $rule['version'], $case['case_id'], $case['episode'], $trigger)));
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
                'result' => $result,
            ), null, hash('sha256', $key . ':result'));
        }
    }

    private function matches(array $rule, array $case): bool
    {
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
                $result = $this->case_service->transition((int) $case['case_id'], $target, '', (int) $case['row_version']);
                return is_wp_error($result) ? 'failed' : 'applied';

            case 'admin_alert':
                do_action('wperh_send_admin_alert', $case, 'workflow.' . $rule['id']);
                return 'queued';
        }

        return 'rejected';
    }
}
