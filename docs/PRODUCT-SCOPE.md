# Product scope and acceptance principles

## User problem

Store teams need a single view of orders that require investigation or follow-up. Exceptions may include aging pending payment, failed payment that may be recoverable, missing/invalid shipping information, stock discrepancy, or processing beyond an agreed service window.

## Primary user outcome

Within one workspace, a staff member can understand why an order needs attention, inspect relevant evidence, choose or assign a next step, record the outcome, and see whether the case was resolved within its target time.

## Non-goals

- Replacing the WooCommerce order editor, payment gateway, inventory manager, shipping label system, or a general automation suite.
- Treating every pending/on-hold order as a failure. Offline and delayed-payment methods can be validly awaiting confirmation.
- Silent refunds, captures, cancellations, or order status changes.
- Requiring WP-CLI or a command-line workflow to install or operate the plugin.

## UX principles

1. Explain why each order is in the queue and how age/priority was calculated.
2. Keep customer/order facts and plugin case-management data visually distinct.
3. Make the safest next step the easiest action; preserve native WooCommerce order controls.
4. Use clear empty, loading, error, permission-denied, and stale-data states.
5. Support keyboard navigation, visible focus, readable contrast, responsive admin layouts, and screen-reader labels.
6. Make automation inspectable: show rule, matched condition, proposed action, run status, and retry outcome.

## MVP acceptance criteria (to refine in Phase 2)

- Staff with the right capabilities can view a paginated exception queue and filter by reason, age, case state, priority, and assignee where available.
- Each queue row links to the native order and explains the exception using accessible text.
- Staff can add an internal note and record a resolution outcome with actor and timestamp.
- Queue data remains accurate with HPOS enabled and does not read order records via direct SQL or WordPress post-meta APIs.
- Repeated order events do not create duplicate cases or duplicate notifications.
- No payment or irreversible action is executed by the MVP.
- No customer-facing content is sent by the MVP.
