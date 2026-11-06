<?php

namespace WPEssential\OrderRescueHub\Pro;

final class Settings
{
    private const OPTION = 'wperh_pro_settings';

    public static function defaults(): array
    {
        return array(
            'alerts_enabled' => false,
            'recipients' => array(),
            'response_hours' => 4,
            'resolution_hours' => 24,
            'warning_percent' => 75,
            'clock_mode' => 'calendar',
            'timezone' => wp_timezone_string(),
            'work_start' => '09:00',
            'work_end' => '17:00',
            'workdays' => array(1, 2, 3, 4, 5),
            'holidays' => array(),
            'pause_states' => array('waiting'),
            'require_approval_for_order_actions' => true,
            'order_action_execution_enabled' => false,
            'rules' => array(),
        );
    }

    public static function get(): array
    {
        return wp_parse_args(get_option(self::OPTION, array()), self::defaults());
    }

    public static function save(array $input): array
    {
        $defaults = self::defaults();
        $timezone = self::text($input['timezone'] ?? $defaults['timezone']);
        try {
            new \DateTimeZone($timezone);
        } catch (\Throwable $exception) {
            $timezone = $defaults['timezone'];
        }

        $workdays_input = is_array($input['workdays'] ?? null) ? $input['workdays'] : $defaults['workdays'];
        $workdays = array_values(array_unique(array_filter(array_map(static function ($day): int { return is_scalar($day) ? absint($day) : 0; }, $workdays_input), static function (int $day): bool {
            return $day >= 1 && $day <= 7;
        })));
        $holidays_input = is_array($input['holidays'] ?? null) ? $input['holidays'] : array();
        $holidays = array_values(array_unique(array_filter(array_map(static function ($date): string { return is_scalar($date) ? sanitize_text_field((string) $date) : ''; }, $holidays_input), static function (string $date): bool {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return false;
            }
            $parts = array_map('intval', explode('-', $date));
            return checkdate($parts[1], $parts[2], $parts[0]);
        })));
        $recipients_input = is_array($input['recipients'] ?? null) ? $input['recipients'] : array();
        $recipients = array_values(array_unique(array_filter(array_map(static function ($email): string { return is_scalar($email) ? sanitize_email((string) $email) : ''; }, $recipients_input), 'is_email')));
        $clock_mode = self::text($input['clock_mode'] ?? 'calendar');
        $pause_input = is_array($input['pause_states'] ?? null) ? $input['pause_states'] : array('waiting');
        $pause_states = array_values(array_intersect(array_map(static function ($state): string { return is_scalar($state) ? sanitize_key((string) $state) : ''; }, $pause_input), array('waiting')));

        $settings = array(
            'alerts_enabled' => !empty($input['alerts_enabled']),
            'recipients' => array_slice($recipients, 0, 20),
            'response_hours' => min(720, max(1, absint($input['response_hours'] ?? 4))),
            'resolution_hours' => min(2160, max(1, absint($input['resolution_hours'] ?? 24))),
            'warning_percent' => min(95, max(25, absint($input['warning_percent'] ?? 75))),
            'clock_mode' => in_array($clock_mode, array('calendar', 'business'), true) ? $clock_mode : 'calendar',
            'timezone' => $timezone,
            'work_start' => self::valid_time($input['work_start'] ?? '09:00', '09:00'),
            'work_end' => self::valid_time($input['work_end'] ?? '17:00', '17:00'),
            'workdays' => $workdays ?: $defaults['workdays'],
            'holidays' => array_slice($holidays, 0, 100),
            'pause_states' => $pause_states,
            'require_approval_for_order_actions' => true,
            'order_action_execution_enabled' => false,
            'rules' => self::sanitize_rules((array) ($input['rules'] ?? array())),
        );

        if ($settings['work_start'] >= $settings['work_end']) {
            $settings['work_start'] = $defaults['work_start'];
            $settings['work_end'] = $defaults['work_end'];
        }

        update_option(self::OPTION, $settings, false);
        return $settings;
    }

    public static function sanitize_rules(array $rules): array
    {
        $clean = array();
        $allowed_triggers = array('case.opened', 'case.age_check', 'sla.warning', 'sla.breached');
        $allowed_actions = array('set_priority', 'assign_case', 'set_case_state', 'admin_alert');
        foreach (array_slice($rules, 0, 20) as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $trigger = is_scalar($rule['trigger'] ?? null) ? sanitize_key((string) $rule['trigger']) : '';
            $action = is_scalar($rule['action'] ?? null) ? sanitize_key((string) $rule['action']) : '';
            if (!in_array($trigger, $allowed_triggers, true) || !in_array($action, $allowed_actions, true)) {
                continue;
            }
            $name = self::text($rule['name'] ?? '');
            if ('' === $name) {
                continue;
            }
            $case_state = is_scalar($rule['case_state'] ?? null) ? sanitize_key((string) $rule['case_state']) : '';
            if ('' !== $case_state && !in_array($case_state, array('open', 'triaged', 'assigned', 'waiting', 'escalated', 'closed'), true)) {
                $case_state = '';
            }
            $value = self::text($rule['value'] ?? '');
            if ('set_case_state' === $action && !in_array($value, array('open', 'triaged', 'waiting'), true)) {
                continue;
            }
            if ('set_priority' === $action && (!is_scalar($rule['value'] ?? null) || absint($rule['value']) < 1 || absint($rule['value']) > 100)) {
                continue;
            }
            if ('assign_case' === $action && (!is_scalar($rule['value'] ?? null) || absint($rule['value']) < 1)) {
                continue;
            }
            $clean[] = array(
                'id' => is_scalar($rule['id'] ?? null) ? sanitize_key((string) $rule['id']) : sanitize_key(wp_generate_uuid4()),
                'name' => $name,
                'enabled' => !empty($rule['enabled']),
                'dry_run' => !array_key_exists('dry_run', $rule) || !empty($rule['dry_run']),
                'trigger' => $trigger,
                'reason' => is_scalar($rule['reason'] ?? null) ? sanitize_key((string) $rule['reason']) : '',
                'case_state' => $case_state,
                'min_age_hours' => min(2160, is_scalar($rule['min_age_hours'] ?? null) ? absint($rule['min_age_hours']) : 0),
                'action' => $action,
                'value' => $value,
                'version' => max(1, is_scalar($rule['version'] ?? null) ? absint($rule['version']) : 1),
            );
        }
        return $clean;
    }

    private static function text($value): string
    {
        return is_scalar($value) ? sanitize_text_field((string) $value) : '';
    }

    private static function valid_time($value, string $fallback): string
    {
        $value = self::text($value);
        return preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $value) ? $value : $fallback;
    }
}
