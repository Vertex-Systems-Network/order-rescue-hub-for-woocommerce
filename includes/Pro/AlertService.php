<?php

namespace WPEssential\OrderRescueHub\Pro;

use WPEssential\OrderRescueHub\Repository\CaseRepository;

final class AlertService
{
    private CaseRepository $repository;

    public function __construct(CaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('wperh_case_detected', array($this, 'on_case_detected'));
        add_action('wperh_sla_event', array($this, 'on_sla_event'), 10, 2);
        add_action('wperh_send_admin_alert', array($this, 'on_workflow_alert'), 10, 2);
        add_action('wperh_retry_alert', array($this, 'retry_alert'), 10, 3);
    }

    public function on_case_detected(array $case): void
    {
        $this->send($case, 'case.opened');
    }

    public function on_workflow_alert(array $case, string $event): void
    {
        $this->send($case, sanitize_key($event));
    }

    public function retry_alert(int $case_id, string $event, int $attempt): void
    {
        $case = $this->repository->find($case_id);
        if ($case && $attempt <= 3) {
            $this->send($case, $event, $attempt);
        }
    }

    public function on_sla_event(array $case, string $event): void
    {
        if (in_array($event, array('warning', 'breached'), true)) {
            $this->send($case, 'sla.' . $event);
        }
    }

    public function test_delivery(): bool
    {
        $settings = Settings::get();
        $recipients = $settings['recipients'];
        if (!$recipients) {
            $recipients = array(get_option('admin_email'));
        }
        $subject = sprintf(__('[%s] Order Rescue Hub alert test', 'wpessential-order-rescue-hub'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $message = __('This is a test alert from WPEssential Order Rescue Hub. No customer or order data is included.', 'wpessential-order-rescue-hub');
        return wp_mail($recipients, $subject, $message);
    }

    private function send(array $case, string $event, int $attempt = 1): void
    {
        $settings = Settings::get();
        if (empty($settings['alerts_enabled'])) {
            return;
        }
        $recipients = $settings['recipients'];
        if (!$recipients) {
            return;
        }

        $key = hash('sha256', implode(':', array((int) $case['case_id'], (int) $case['episode'], $event, $attempt)));
        if ($this->repository->has_event($key)) {
            return;
        }
        $lock_key = 'wperh_mail_lock_' . substr($key, 0, 32);
        if (!add_option($lock_key, time(), '', false)) {
            $locked_at = (int) get_option($lock_key, 0);
            if ($locked_at && time() - $locked_at > 600) {
                delete_option($lock_key);
                if (!add_option($lock_key, time(), '', false)) {
                    return;
                }
            } else {
                return;
            }
        }

        $url = admin_url('admin.php?page=wperh-order-rescue-hub');
        $subject = sprintf(__('[%s] Order exception needs review', 'wpessential-order-rescue-hub'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $message = sprintf(
            __("An order exception case needs review. Case #%1$d, order #%2$d. Open the Order Rescue Hub: %3$s", 'wpessential-order-rescue-hub'),
            (int) $case['case_id'],
            (int) $case['order_id'],
            esc_url_raw($url)
        );
        $sent = false;
        try {
            $sent = wp_mail($recipients, $subject, $message);
            $this->repository->record_event(
                (int) $case['case_id'],
                'alert_delivery',
                array('event' => $event, 'attempt' => $attempt, 'result' => $sent ? 'sent' : 'failed'),
                null,
                $key
            );
            if (!$sent && $attempt < 3) {
                wp_schedule_single_event(time() + (300 * $attempt), 'wperh_retry_alert', array((int) $case['case_id'], $event, $attempt + 1));
            }
        } finally {
            delete_option($lock_key);
        }
    }
}
