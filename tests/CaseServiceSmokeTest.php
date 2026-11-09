<?php

namespace WPEssential\OrderRescueHub\Repository {
    final class CaseRepository
    {
        public array $case;
        public array $notes = array();
        public array $updates = array();
        public bool $reject_next_update = false;

        public function __construct(array $case)
        {
            $this->case = $case;
        }

        public function find(int $case_id): ?array
        {
            return (int) $this->case['case_id'] === $case_id ? $this->case : null;
        }

        public function add_note(int $case_id, string $note): bool
        {
            $this->notes[] = array($case_id, $note);
            return true;
        }

        public function update_case(array $case, array $changes, string $event_type, array $payload): bool
        {
            $this->updates[] = array($changes, $event_type, $payload);
            if ($this->reject_next_update) {
                $this->reject_next_update = false;
                return false;
            }
            $this->case = array_merge($this->case, $changes);
            $this->case['row_version'] = (int) $case['row_version'] + 1;
            return true;
        }
    }
}

namespace WPEssential\OrderRescueHub\Service {
    require dirname(__DIR__) . '/includes/Service/CaseService.php';
}

namespace {
    final class WP_Error
    {
        private string $code;
        private array $data;

        public function __construct(string $code, string $message = '', array $data = array())
        {
            $this->code = $code;
            $this->data = $data;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_data(): array
        {
            return $this->data;
        }
    }

    function sanitize_textarea_field(string $value): string
    {
        return trim(strip_tags($value));
    }

    function __(string $text, string $domain = ''): string
    {
        return $text;
    }

    function do_action(string $hook, ...$args): void
    {
    }

    function fail_service_test(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    $base_case = array(
        'case_id' => 12,
        'order_id' => 49,
        'state' => 'open',
        'row_version' => 2,
        'episode' => 1,
        'sla_response_due_at' => '2026-11-08 12:00:00',
        'sla_response_met_at' => '2026-11-07 12:00:00',
        'sla_resolution_due_at' => '2026-11-09 12:00:00',
        'sla_state' => 'breached',
        'sla_paused_at' => '2026-11-07 11:00:00',
    );
    $repository = new \WPEssential\OrderRescueHub\Repository\CaseRepository($base_case);
    $service = new \WPEssential\OrderRescueHub\Service\CaseService($repository);

    if (true !== $service->add_note(12, '<script>alert(1)</script>Keep this note')) {
        fail_service_test('A valid internal note should be accepted.');
    }
    if (1 !== count($repository->notes) || false !== strpos($repository->notes[0][1], '<script')) {
        fail_service_test('HTML tags must not be retained in an internal note.');
    }

    $long_note = $service->add_note(12, str_repeat('x', 2001));
    if (!$long_note instanceof WP_Error || 'wperh_note_invalid' !== $long_note->get_error_code()) {
        fail_service_test('Notes longer than 2,000 characters must be rejected.');
    }
    $empty_note = $service->add_note(12, " \n ");
    if (!$empty_note instanceof WP_Error || 'wperh_note_invalid' !== $empty_note->get_error_code()) {
        fail_service_test('Empty notes must be rejected.');
    }

    $invalid_transition = $service->transition(12, 'refunded', '', 2);
    if (!$invalid_transition instanceof WP_Error || 'wperh_transition_invalid' !== $invalid_transition->get_error_code() || $repository->updates) {
        fail_service_test('Unsupported case transitions must be rejected without writes.');
    }

    $stale_transition = $service->transition(12, 'triaged', '', 1);
    if (!$stale_transition instanceof WP_Error || 'wperh_case_stale' !== $stale_transition->get_error_code() || $repository->updates) {
        fail_service_test('A stale row version must be rejected without writes.');
    }

    $missing_outcome = $service->transition(12, 'closed', '', 2);
    if (!$missing_outcome instanceof WP_Error || 'wperh_outcome_required' !== $missing_outcome->get_error_code() || $repository->updates) {
        fail_service_test('Closing a case must require a valid outcome.');
    }

    $repository->reject_next_update = true;
    $conflict = $service->transition(12, 'triaged', '', 2);
    if (!$conflict instanceof WP_Error || 'wperh_case_conflict' !== $conflict->get_error_code() || 'open' !== $repository->case['state']) {
        fail_service_test('A concurrent row-version conflict must not be reported as a successful transition.');
    }

    $closed = $service->transition(12, 'closed', 'resolved', 2);
    if (!$closed || 'closed' !== $closed['state'] || 'resolved' !== $closed['outcome'] || 'breached' !== $closed['sla_state']) {
        fail_service_test('A valid close transition with an outcome should persist once and preserve its audit state.');
    }

    $reopened = $service->transition(12, 'open', '', 3);
    if (!$reopened || 'open' !== $reopened['state'] || 2 !== $reopened['episode'] || '' !== $reopened['sla_state']
        || null !== $reopened['sla_response_due_at'] || null !== $reopened['sla_response_met_at']
        || null !== $reopened['sla_resolution_due_at'] || null !== $reopened['sla_paused_at']
        || '' !== $reopened['outcome'] || null !== $reopened['closed_at']) {
        fail_service_test('Reopening must start a new episode and clear stale closure and SLA fields.');
    }

    fwrite(STDOUT, "Case service validation smoke checks passed.\n");
}
