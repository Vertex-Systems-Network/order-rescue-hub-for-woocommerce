<?php

namespace WPEssential\OrderRescueHub\Pro;

use WPEssential\OrderRescueHub\Repository\CaseRepository;
use WP_Error;

final class AssignmentService
{
    private CaseRepository $repository;

    public function __construct(CaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function assign(int $case_id, int $user_id)
    {
        $case = $this->repository->find($case_id);
        if (!$case) {
            return new WP_Error('wperh_case_missing', __('Case not found.', 'wpessential-order-rescue-hub'), array('status' => 404));
        }

        if ('closed' === $case['state']) {
            return new WP_Error('wperh_case_closed', __('Closed cases cannot be reassigned.', 'wpessential-order-rescue-hub'), array('status' => 409));
        }

        if (0 === $user_id) {
            if (empty($case['assignee_id'])) {
                return $case;
            }
            $old = $case['assignee_id'];
            $state = 'assigned' === $case['state'] ? 'open' : $case['state'];
            if (!$this->repository->update_case($case, array('assignee_id' => null, 'state' => $state), 'case_unassigned', array('previous_user_id' => $old))) {
                return new WP_Error('wperh_assignment_stale', __('This case changed. Refresh it and try again.', 'wpessential-order-rescue-hub'), array('status' => 409));
            }
            return $this->repository->find($case_id);
        }

        $user = get_user_by('id', $user_id);
        if (!$user || !user_can($user, 'edit_shop_orders')) {
            return new WP_Error('wperh_assignee_invalid', __('Choose a staff member who can edit WooCommerce orders.', 'wpessential-order-rescue-hub'), array('status' => 400));
        }

        if ((int) $case['assignee_id'] === $user_id) {
            return $case;
        }

        $old = $case['assignee_id'];
        $ok = $this->repository->update_case(
            $case,
            array('assignee_id' => $user_id, 'state' => 'assigned'),
            'case_assigned',
            array('previous_user_id' => $old, 'new_user_id' => $user_id)
        );
        if (!$ok) {
            return new WP_Error('wperh_assignment_stale', __('This case changed. Refresh it and try again.', 'wpessential-order-rescue-hub'), array('status' => 409));
        }

        $updated = $this->repository->find($case_id);
        do_action('wperh_case_assigned', $updated, $old, $user_id);
        return $updated;
    }
}
