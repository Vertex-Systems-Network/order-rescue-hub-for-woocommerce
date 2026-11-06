<?php

namespace WPEssential\OrderRescueHub\Repository;

final class CaseRepository
{
    private string $cases;
    private string $events;

    public function __construct()
    {
        global $wpdb;
        $this->cases = $wpdb->prefix . 'wperh_cases';
        $this->events = $wpdb->prefix . 'wperh_case_events';
    }

    public function find(int $case_id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->cases} WHERE case_id = %d", $case_id),
            ARRAY_A
        );

        return $row ? $this->normalize_case($row) : null;
    }

    public function find_by_order(int $order_id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->cases} WHERE order_id = %d", $order_id),
            ARRAY_A
        );

        return $row ? $this->normalize_case($row) : null;
    }

    public function list(array $filters): array
    {
        global $wpdb;

        $where = array('1=1');
        $values = array();

        if (!empty($filters['state']) && 'all' !== $filters['state']) {
            $where[] = 'state = %s';
            $values[] = $filters['state'];
        }

        if (isset($filters['assignee_id']) && '' !== (string) $filters['assignee_id']) {
            $where[] = 'assignee_id = %d';
            $values[] = absint($filters['assignee_id']);
        }

        if (!empty($filters['sla_state']) && 'all' !== $filters['sla_state']) {
            $where[] = 'sla_state = %s';
            $values[] = sanitize_key($filters['sla_state']);
        }

        if (isset($filters['priority']) && in_array($filters['priority'], array('high', 'normal'), true)) {
            $where[] = 'high' === $filters['priority'] ? 'priority <= %d' : 'priority > %d';
            $values[] = 20;
        }

        if (!empty($filters['reason'])) {
            $where[] = 'reason_codes LIKE %s';
            $values[] = '%' . $wpdb->esc_like('"' . $filters['reason'] . '"') . '%';
        }

        if (!empty($filters['search'])) {
            $where[] = 'CAST(order_id AS CHAR) LIKE %s';
            $values[] = '%' . $wpdb->esc_like($filters['search']) . '%';
        }

        if (!empty($filters['older_than_hours'])) {
            $hours = absint($filters['older_than_hours']);
            $cutoff = gmdate('Y-m-d H:i:s', time() - ($hours * HOUR_IN_SECONDS));
            $where[] = 'first_detected_at <= %s';
            $values[] = $cutoff;
        }

        $where_sql = implode(' AND ', $where);
        $page = max(1, absint($filters['page'] ?? 1));
        $per_page = min(100, max(1, absint($filters['per_page'] ?? 25)));
        $offset = ($page - 1) * $per_page;

        $count_sql = "SELECT COUNT(*) FROM {$this->cases} WHERE {$where_sql}";
        $query_sql = "SELECT case_id, order_id, state, reason_codes, evidence, priority, first_detected_at, last_detected_at, created_at, updated_at, closed_at, outcome, episode, row_version, assignee_id, sla_response_due_at, sla_response_met_at, sla_resolution_due_at, sla_state, sla_paused_at FROM {$this->cases} WHERE {$where_sql} ORDER BY priority ASC, first_detected_at ASC LIMIT %d OFFSET %d";

        $count = (int) $wpdb->get_var($values ? $wpdb->prepare($count_sql, $values) : $count_sql);
        $query_values = array_merge($values, array($per_page, $offset));
        $rows = $wpdb->get_results($wpdb->prepare($query_sql, $query_values), ARRAY_A);

        return array(
            'items' => array_map(array($this, 'normalize_case'), $rows ?: array()),
            'total' => $count,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => (int) ceil($count / $per_page),
        );
    }

    public function events(int $case_id, int $limit = 100): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_id, event_type, actor_id, occurred_at, payload FROM {$this->events} WHERE case_id = %d ORDER BY event_id DESC LIMIT %d",
                $case_id,
                min(200, max(1, $limit))
            ),
            ARRAY_A
        );

        return array_map(static function (array $row): array {
            $row['payload'] = json_decode((string) $row['payload'], true) ?: array();
            return $row;
        }, $rows ?: array());
    }

    public function detect(int $order_id, array $reasons, array $evidence): ?array
    {
        global $wpdb;

        $now = gmdate('Y-m-d H:i:s');
        $reason_codes = array_values(array_unique(array_map('sanitize_key', $reasons)));
        sort($reason_codes);
        $evidence = $this->redact_evidence($evidence);
        $priority = in_array('failed_payment', $reason_codes, true) ? 10 : (in_array('pending_payment', $reason_codes, true) ? 20 : (array_intersect($reason_codes, array('missing_shipping', 'stock_discrepancy')) ? 30 : 40));
        $existing = $this->find_by_order($order_id);

        if (!$existing && !$reason_codes) {
            return null;
        }

        if (!$existing) {
            $wpdb->insert(
                $this->cases,
                array(
                    'order_id' => $order_id,
                    'state' => 'open',
                    'reason_codes' => wp_json_encode($reason_codes),
                    'evidence' => wp_json_encode($evidence),
                    'priority' => $priority,
                    'first_detected_at' => $now,
                    'last_detected_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'episode' => 1,
                    'row_version' => 1,
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d')
            );
            $case_id = (int) $wpdb->insert_id;
            if (!$case_id) {
                return $this->find_by_order($order_id);
            }
            $this->add_event($case_id, 'detected', array('reasons' => $reason_codes), null, 'detect:' . $order_id . ':' . hash('sha256', wp_json_encode($reason_codes) . gmdate('YmdH')));
            $case = $this->find($case_id);
            if ($case) {
                do_action('wperh_case_detected', $case);
            }
            return $case;
        }

        $was_closed = 'closed' === $existing['state'];
        if (!$was_closed && $reason_codes === $existing['reason_codes'] && $evidence === $existing['evidence']) {
            return $existing;
        }
        $wpdb->update(
            $this->cases,
            array(
                'state' => $was_closed && $reason_codes ? 'open' : $existing['state'],
                'reason_codes' => wp_json_encode($reason_codes ?: $existing['reason_codes']),
                'priority' => $priority,
                'evidence' => wp_json_encode($evidence),
                'first_detected_at' => $was_closed && $reason_codes ? $now : $existing['first_detected_at'],
                'last_detected_at' => $now,
                'updated_at' => $now,
                'closed_at' => $was_closed && $reason_codes ? null : $existing['closed_at'],
                'outcome' => $was_closed && $reason_codes ? '' : $existing['outcome'],
                'sla_response_due_at' => $was_closed && $reason_codes ? null : $existing['sla_response_due_at'],
                'sla_response_met_at' => $was_closed && $reason_codes ? null : $existing['sla_response_met_at'],
                'sla_resolution_due_at' => $was_closed && $reason_codes ? null : $existing['sla_resolution_due_at'],
                'sla_state' => $was_closed && $reason_codes ? '' : $existing['sla_state'],
                'sla_paused_at' => $was_closed && $reason_codes ? null : $existing['sla_paused_at'],
                'episode' => $was_closed && $reason_codes ? (int) $existing['episode'] + 1 : (int) $existing['episode'],
                'row_version' => (int) $existing['row_version'] + 1,
            ),
            array('case_id' => (int) $existing['case_id']),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
            array('%d')
        );

        $event_type = $was_closed && $reason_codes ? 'reopened' : 'detection_updated';
        $this->add_event(
            (int) $existing['case_id'],
            $event_type,
            array('reasons' => $reason_codes),
            null,
            'detect:' . $order_id . ':' . hash('sha256', wp_json_encode($reason_codes) . gmdate('YmdH'))
        );

        $updated = $this->find((int) $existing['case_id']);
        if ($updated && $was_closed && $reason_codes) {
            do_action('wperh_case_detected', $updated);
        }
        return $updated;
    }

    public function resolve_if_clear(int $order_id): void
    {
        $case = $this->find_by_order($order_id);
        if (!$case || 'closed' === $case['state']) {
            return;
        }
        $now = gmdate('Y-m-d H:i:s');
        $updated = $this->update_case(
            $case,
            array('state' => 'closed', 'closed_at' => $now, 'outcome' => 'resolved'),
            'case_auto_resolved',
            array('reason' => 'no_active_exception')
        );
        if ($updated) {
            do_action('wperh_case_transitioned', $order_id === (int) $case['order_id'] ? (int) $case['case_id'] : 0, 'closed');
        }
    }

    public function update_case(array $case, array $changes, string $event_type, array $payload): bool
    {
        global $wpdb;

        $changes['updated_at'] = gmdate('Y-m-d H:i:s');
        $changes['row_version'] = (int) $case['row_version'] + 1;
        $updated = $wpdb->update(
            $this->cases,
            $changes,
            array('case_id' => (int) $case['case_id'], 'row_version' => (int) $case['row_version'])
        );

        if (1 !== $updated) {
            return false;
        }

        $event_key = hash('sha256', implode(':', array(
            (int) $case['case_id'],
            wp_generate_uuid4(),
        )));
        $this->add_event((int) $case['case_id'], $event_type, $payload, get_current_user_id(), $event_key);

        return true;
    }

    public function has_event(string $idempotency_key): bool
    {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT event_id FROM {$this->events} WHERE idempotency_key = %s LIMIT 1", substr($idempotency_key, 0, 64)));
    }

    public function record_event(int $case_id, string $event_type, array $payload, ?int $actor_id, string $idempotency_key): bool
    {
        return $this->add_event($case_id, $event_type, $payload, $actor_id, $idempotency_key);
    }

    public function add_note(int $case_id, string $note): bool
    {
        $event_key = hash('sha256', $case_id . ':note:' . wp_generate_uuid4());
        return $this->add_event($case_id, 'note_added', array('note' => $note), get_current_user_id(), $event_key);
    }

    private function add_event(int $case_id, string $type, array $payload, ?int $actor_id, string $event_key): bool
    {
        global $wpdb;

        $result = $wpdb->insert(
            $this->events,
            array(
                'case_id' => $case_id,
                'idempotency_key' => substr($event_key, 0, 64),
                'event_type' => sanitize_key($type),
                'actor_id' => $actor_id,
                'occurred_at' => gmdate('Y-m-d H:i:s'),
                'payload' => wp_json_encode($payload),
            )
        );

        return false !== $result;
    }

    private function normalize_case(array $row): array
    {
        $row['case_id'] = (int) $row['case_id'];
        $row['order_id'] = (int) $row['order_id'];
        $row['priority'] = (int) $row['priority'];
        $row['episode'] = (int) $row['episode'];
        $row['row_version'] = (int) $row['row_version'];
        $row['assignee_id'] = $row['assignee_id'] === null ? null : (int) $row['assignee_id'];
        $row['reason_codes'] = json_decode((string) $row['reason_codes'], true) ?: array();
        $row['evidence'] = json_decode((string) $row['evidence'], true) ?: array();
        return $row;
    }

    private function redact_evidence(array $evidence): array
    {
        $allowed = array('status', 'payment_method', 'shipping_missing', 'processing_age_hours', 'stock_issue');
        $clean = array();

        foreach ($allowed as $key) {
            if (array_key_exists($key, $evidence)) {
                $clean[$key] = is_scalar($evidence[$key]) ? $evidence[$key] : '';
            }
        }

        return $clean;
    }
}
