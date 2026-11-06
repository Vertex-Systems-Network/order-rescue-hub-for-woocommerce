<?php

namespace WPEssential\OrderRescueHub\Pro;

use WPEssential\OrderRescueHub\Repository\CaseRepository;

final class SlaClock
{
    private CaseRepository $repository;

    public function __construct(CaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function start(array $case): void
    {
        if ('closed' === $case['state'] || !empty($case['sla_resolution_due_at'])) {
            return;
        }

        $settings = Settings::get();
        $zone = new \DateTimeZone($settings['timezone']);
        $now = new \DateTimeImmutable('now', $zone);
        $response = $this->due_after($now, (int) $settings['response_hours'], $settings);
        $resolution = $this->due_after($now, (int) $settings['resolution_hours'], $settings);
        $this->repository->update_case(
            $case,
            array(
                'sla_response_due_at' => $response->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                'sla_resolution_due_at' => $resolution->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                'sla_state' => 'running',
            ),
            'sla_started',
            array('response_hours' => (int) $settings['response_hours'], 'resolution_hours' => (int) $settings['resolution_hours'], 'mode' => $settings['clock_mode'])
        );
    }

    public function on_note_added(int $case_id): void
    {
        $case = $this->repository->find($case_id);
        if (!$case || !empty($case['sla_response_met_at'])) {
            return;
        }
        $now = gmdate('Y-m-d H:i:s');
        if ($this->repository->update_case($case, array('sla_response_met_at' => $now), 'sla_response_met', array('source' => 'private_note'))) {
            $this->evaluate($this->repository->find($case_id));
        }
    }

    public function on_case_transitioned(int $case_id, string $state): void
    {
        $case = $this->repository->find($case_id);
        if ($case) {
            $this->evaluate($case);
        }
    }

    public function evaluate_all(): void
    {
        $first = $this->repository->list(array('state' => 'all', 'page' => 1, 'per_page' => 100));
        $total_pages = max(1, (int) $first['total_pages']);
        $page = min(max(1, absint(get_option('wperh_sla_sweep_page', 1))), $total_pages);
        $batches = min($total_pages, 10);

        for ($index = 0; $index < $batches; $index++) {
            $batch = 1 === $page
                ? $first
                : $this->repository->list(array('state' => 'all', 'page' => $page, 'per_page' => 100));
            foreach ($batch['items'] as $case) {
                $this->evaluate($case);
            }
            $page = $page >= $total_pages ? 1 : $page + 1;
        }

        update_option('wperh_sla_sweep_page', $page, false);
    }

    public function evaluate(array $case): void
    {
        if (empty($case['sla_resolution_due_at'])) {
            if ('closed' !== $case['state']) {
                $this->start($case);
            }
            return;
        }

        $settings = Settings::get();
        $paused_states = $settings['pause_states'];
        $is_paused = in_array($case['state'], $paused_states, true);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $response_due = $this->utc_date($case['sla_response_due_at']);
        $resolution_due = $this->utc_date($case['sla_resolution_due_at']);

        if ('closed' === $case['state']) {
            $closed_at = !empty($case['closed_at']) ? $this->utc_date($case['closed_at']) : $now;
            if (!empty($case['sla_paused_at'])) {
                $paused_at = $this->utc_date($case['sla_paused_at']);
                $paused_seconds = 'business' === $settings['clock_mode']
                    ? $this->business_elapsed_seconds($paused_at, $closed_at, $settings)
                    : max(0, $closed_at->getTimestamp() - $paused_at->getTimestamp());
                $response_due = $this->utc_date($this->shift_due($case['sla_response_due_at'], $paused_seconds));
                $resolution_due = $this->utc_date($this->shift_due($case['sla_resolution_due_at'], $paused_seconds));
            }
            $response_met_at = !empty($case['sla_response_met_at']) ? $this->utc_date($case['sla_response_met_at']) : null;
            $response_missed = $response_met_at ? $response_met_at > $response_due : $closed_at > $response_due;
            $breached = $resolution_due < $closed_at || $response_missed;
            $next = $breached ? 'breached' : 'met';
            if ($next !== $case['sla_state']) {
                $this->change_sla($case, array('sla_state' => $next, 'sla_paused_at' => null), 'sla_' . $next);
                if ($breached) {
                    do_action('wperh_sla_event', $this->repository->find((int) $case['case_id']), $next);
                }
            }
            return;
        }

        if ($is_paused && empty($case['sla_paused_at'])) {
            $this->change_sla($case, array('sla_state' => 'paused', 'sla_paused_at' => $now->format('Y-m-d H:i:s')), 'sla_paused');
            return;
        }

        if (!$is_paused && !empty($case['sla_paused_at'])) {
            $paused_at = new \DateTimeImmutable($case['sla_paused_at'], new \DateTimeZone('UTC'));
            $seconds = 'business' === $settings['clock_mode']
                ? $this->business_elapsed_seconds($paused_at, $now, $settings)
                : max(0, $now->getTimestamp() - $paused_at->getTimestamp());
            $changes = array(
                'sla_state' => 'running',
                'sla_paused_at' => null,
                'sla_response_due_at' => $this->shift_due($case['sla_response_due_at'], $seconds),
                'sla_resolution_due_at' => $this->shift_due($case['sla_resolution_due_at'], $seconds),
            );
            $this->change_sla($case, $changes, 'sla_resumed', array('paused_seconds' => $seconds));
            return;
        }

        $active_due = empty($case['sla_response_met_at']) ? $response_due : $resolution_due;
        $active_target = empty($case['sla_response_met_at']) ? (int) $settings['response_hours'] : (int) $settings['resolution_hours'];
        $response_warning = $active_due > $now && ($active_due->getTimestamp() - $now->getTimestamp()) <= ($active_target * HOUR_IN_SECONDS * (100 - (int) $settings['warning_percent']) / 100);
        $response_met_at = !empty($case['sla_response_met_at']) ? $this->utc_date($case['sla_response_met_at']) : null;
        $response_missed = $response_met_at ? $response_met_at > $response_due : $response_due <= $now;
        $breached = $response_missed || $resolution_due <= $now;
        $next = $breached ? 'breached' : ($response_warning ? 'warning' : 'running');

        if ($next !== $case['sla_state']) {
            $this->change_sla($case, array('sla_state' => $next), 'sla_' . $next, array('response_due_at' => $case['sla_response_due_at'], 'resolution_due_at' => $case['sla_resolution_due_at']));
            do_action('wperh_sla_event', $this->repository->find((int) $case['case_id']), $next);
        }
    }

    private function due_after(\DateTimeImmutable $start, int $hours, array $settings): \DateTimeImmutable
    {
        if ('calendar' === $settings['clock_mode']) {
            return $start->modify('+' . $hours . ' hours');
        }

        $remaining = $hours * HOUR_IN_SECONDS;
        $date = $start;
        $guard = 0;
        while ($remaining > 0 && $guard++ < 4000) {
            $day = (int) $date->format('N');
            $date_key = $date->format('Y-m-d');
            $is_workday = in_array($day, $settings['workdays'], true) && !in_array($date_key, $settings['holidays'], true);
            $begin = new \DateTimeImmutable($date_key . ' ' . $settings['work_start'], $date->getTimezone());
            $end = new \DateTimeImmutable($date_key . ' ' . $settings['work_end'], $date->getTimezone());
            if ($is_workday && $date < $end) {
                if ($date < $begin) {
                    $date = $begin;
                }
                $available = max(0, $end->getTimestamp() - $date->getTimestamp());
                $use = min($remaining, $available);
                $date = $date->modify('+' . $use . ' seconds');
                $remaining -= $use;
                if ($remaining <= 0) {
                    break;
                }
            }
            $date = new \DateTimeImmutable($date_key . ' 00:00:00', $date->getTimezone())->modify('+1 day');
        }

        return $date;
    }

    private function business_elapsed_seconds(\DateTimeImmutable $start_utc, \DateTimeImmutable $end_utc, array $settings): int
    {
        $zone = new \DateTimeZone($settings['timezone']);
        $start = $start_utc->setTimezone($zone);
        $end = $end_utc->setTimezone($zone);
        $seconds = 0;
        $cursor = $start->setTime(0, 0);
        $last_day = $end->setTime(0, 0);
        $guard = 0;
        while ($cursor <= $last_day && $guard++ < 4000) {
            $date_key = $cursor->format('Y-m-d');
            if (in_array((int) $cursor->format('N'), $settings['workdays'], true) && !in_array($date_key, $settings['holidays'], true)) {
                $open = new \DateTimeImmutable($date_key . ' ' . $settings['work_start'], $zone);
                $close = new \DateTimeImmutable($date_key . ' ' . $settings['work_end'], $zone);
                $from = $start > $open ? $start : $open;
                $to = $end < $close ? $end : $close;
                if ($to > $from) {
                    $seconds += $to->getTimestamp() - $from->getTimestamp();
                }
            }
            $cursor = $cursor->modify('+1 day');
        }
        return $seconds;
    }

    private function utc_date(string $value): \DateTimeImmutable
    {
        return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
    }

    private function shift_due(string $value, int $seconds): string
    {
        return $this->utc_date($value)->modify('+' . $seconds . ' seconds')->format('Y-m-d H:i:s');
    }

    private function change_sla(array $case, array $changes, string $event, array $payload = array()): void
    {
        $this->repository->update_case($case, $changes, $event, $payload);
    }
}
