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
        $timezone = sanitize_text_field((string) ($input['timezone'] ?? $defaults['timezone']));
        try {
            new \DateTimeZone($timezone);
        } catch (\Throwable $exception) {
            $timezone = $defaults['timezone'];
        }

        $workdays = array_values(array_unique(array_filter(array_map('absint', (array) ($input['workdays'] ?? $defaults['workdays'])), static function (int $day): bool {
            return $day >= 1 && $day <= 7;
        })));
        $holidays = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) ($input['holidays'] ?? array())), static function (string $date): bool {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            return $parsed && $parsed->format('Y-m-d') === $date;
        })));
        $recipients = array_values(array_unique(array_filter(array_map('sanitize_email', (array) ($input['recipients'] ?? array())), 'is_email')));
        $pause_states = array_values(array_intersect(array_map('sanitize_key', (array) ($input['pause_states'] ?? array('waiting'))), array('waiting')));

        $settings = array(
            'alerts_enabled' => !empty($input['alerts_enabled']),
            'recipients' => array_slice($recipients, 0, 20),
            'response_hours' => min(720, max(1, absint($input['response_hours'] ?? 4))),
            'resolution_hours' => min(2160, max(1, absint($input['resolution_hours'] ?? 24))),
            'warning_percent' => min(95, max(25, absint($input['warning_percent'] ?? 75))),
            'clock_mode' => in_array(($input['clock_mode'] ?? 'calendar'), array('calendar', 'business'), true) ? $input['clock_mode'] : 'calendar',
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
            $trigger = sanitize_key((string) ($rule['trigger'] ?? ''));
            $action = sanitize_key((string) ($rule['action'] ?? ''));
            if (!in_array($trigger, $allowed_triggers, true) || !in_array($action, $allowed_actions, true)) {
                continue;
            }
            $name = sanitize_text_field((string) ($rule['name'] ?? ''));
            if ('' === $name) {
                continue;
            }
            if ('set_case_state' === $action && !in_array(($rule['value'] ?? ''), array('open', 'triaged', 'waiting'), true)) {
                continue;
            }
            if ('set_priority' === $action && (absint($rule['value'] ?? 0) < 1 || absint($rule['value'] ?? 0) > 100)) {
                continue;
            }
            if ('assign_case' === $action && absint($rule['value'] ?? 0) < 1) {
                continue;
            }
            $clean[] = array(
                'id' => sanitize_key((string) ($rule['id'] ?? wp_generate_uuid4())),
                'name' => $name,
                'enabled' => !empty($rule['enabled']),
                'dry_run' => !array_key_exists('dry_run', $rule) || !empty($rule['dry_run']),
                'trigger' => $trigger,
                'reason' => sanitize_key((string) ($rule['reason'] ?? '')),
                'min_age_hours' => min(2160, absint($rule['min_age_hours'] ?? 0)),
                'action' => $action,
                'value' => sanitize_text_field((string) ($rule['value'] ?? '')),
                'version' => max(1, absint($rule['version'] ?? 1)),
            );
        }
        return $clean;
    }

    private static function valid_time($value, string $fallback): string
    {
        $value = sanitize_text_field((string) $value);
        return preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $value) ? $value : $fallback;
    }
}
