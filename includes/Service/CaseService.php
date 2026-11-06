<?php

namespace WPEssential\OrderRescueHub\Service;

use WPEssential\OrderRescueHub\Repository\CaseRepository;
use WP_Error;

final class CaseService
{
    private CaseRepository $repository;

    public function __construct(CaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function add_note(int $case_id, string $raw_note)
    {
        $case = $this->repository->find($case_id);
        if (!$case) {
            return new WP_Error('wperh_case_missing', __('Case not found.', 'wpessential-order-rescue-hub'), array('status' => 404));
        }

        $note = trim(sanitize_textarea_field($raw_note));
        $note_length = function_exists('mb_strlen') ? mb_strlen($note) : strlen($note);
        if ('' === $note || $note_length > 2000) {
            return new WP_Error('wperh_note_invalid', __('Enter a note of 1 to 2,000 characters.', 'wpessential-order-rescue-hub'), array('status' => 400));
        }

        if (!$this->repository->add_note($case_id, $note)) {
            return new WP_Error('wperh_note_failed', __('The private note could not be saved.', 'wpessential-order-rescue-hub'), array('status' => 500));
        }

        do_action('wperh_case_note_added', $case_id);
        return true;
    }

    public function transition(int $case_id, string $target, string $outcome, int $expected_version)
    {
        $case = $this->repository->find($case_id);
        if (!$case) {
            return new WP_Error('wperh_case_missing', __('Case not found.', 'wpessential-order-rescue-hub'), array('status' => 404));
        }

        if ((int) $case['row_version'] !== $expected_version) {
            return new WP_Error('wperh_case_stale', __('This case changed in another session. Refresh it and try again.', 'wpessential-order-rescue-hub'), array('status' => 409));
        }

        $allowed = array(
            'open' => array('triaged', 'waiting', 'closed'),
            'triaged' => array('open', 'waiting', 'closed'),
            'waiting' => array('open', 'triaged', 'closed'),
            'assigned' => array('open', 'waiting', 'closed'),
            'escalated' => array('open', 'waiting', 'closed'),
            'closed' => array('open'),
        );

        if (!in_array($target, $allowed[$case['state']] ?? array(), true)) {
            return new WP_Error('wperh_transition_invalid', __('That case transition is not allowed.', 'wpessential-order-rescue-hub'), array('status' => 400));
        }

        $valid_outcomes = array('resolved', 'dismissed', 'duplicate', 'no_action_required');
        if ('closed' === $target && !in_array($outcome, $valid_outcomes, true)) {
            return new WP_Error('wperh_outcome_required', __('Choose a resolution outcome before closing the case.', 'wpessential-order-rescue-hub'), array('status' => 400));
        }

        $changes = array('state' => $target);
        $payload = array('from' => $case['state'], 'to' => $target);
        if ('closed' === $case['state'] && 'open' === $target) {
            $changes['episode'] = (int) $case['episode'] + 1;
            $changes['first_detected_at'] = gmdate('Y-m-d H:i:s');
            $changes['outcome'] = '';
            $changes['sla_response_due_at'] = null;
            $changes['sla_response_met_at'] = null;
            $changes['sla_resolution_due_at'] = null;
            $changes['sla_state'] = '';
            $changes['sla_paused_at'] = null;
            $payload['episode'] = $changes['episode'];
        }

        if ('closed' === $target) {
            $changes['closed_at'] = gmdate('Y-m-d H:i:s');
            $changes['outcome'] = $outcome;
            $payload['outcome'] = $outcome;
        } elseif ('open' === $target) {
            $changes['closed_at'] = null;
            $changes['outcome'] = '';
        }

        if (!$this->repository->update_case($case, $changes, 'state_changed', $payload)) {
            return new WP_Error('wperh_case_conflict', __('The case changed while you were saving. Refresh and try again.', 'wpessential-order-rescue-hub'), array('status' => 409));
        }

        return $this->repository->find($case_id);
    }
}
