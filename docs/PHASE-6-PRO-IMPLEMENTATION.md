# Phase 6 — Pro capability implementation

**Status:** Complete for the scoped Pro capability source  
**Completed:** 2026-11-06  
**Paid release status:** Not packaged, licensed, compatibility-tested, or submitted

## Delivered capabilities

- Administrator email alerts are opt-in, use explicitly configured administrator recipients, deduplicate by case episode/event, record delivery outcomes, and retry failed sends at most three times. A test-delivery action is available to a WooCommerce manager.
- Team assignment and reassignment use users with `edit_shop_orders`. The assignment is Hub routing metadata only; it does not grant order access. Case list/detail REST handlers continue to check the caller's existing WooCommerce order capability.
- SLA policy supports calendar or business hours, response and resolution targets, store timezone, weekly workdays, holiday dates, warning thresholds, and pausing while a case waits. Private staff notes mark first response. Pause/resume shifts deadlines by calendar or scheduled working time as configured.
- SLA states and due times are visible in the Pro queue and case details. Outcomes use actual staff-note/close timestamps, include paused time, and start a new SLA episode when a case reopens. Owner and SLA-state filters are available. The shared queue also supports priority filtering and direct links to the native WooCommerce order editor.
- Workflow settings support up to 20 named/versioned rules, with case-open, scheduled age-check, SLA-warning, and SLA-breach triggers; AND conditions for reason, case state, and minimum age; and internal actions to set case priority/state, assign staff, or notify administrators.
- Rules default to dry-run. Disabled rules do not execute. Run/preview/result events are stored in the existing case history. Failed/stale internal runs receive at most three attempts. Workflows cannot access order payment/cancel/status mutation APIs.
- Settings enforce the approval boundary: high-impact order-action execution remains disabled. There is no capture, refund, cancel, or payment-status action route in this implementation.
- Pro service source lives under `includes/Pro/` and is entered only when a Pro build defines `WPERH_PRO_EDITION`. The shared Composer PSR-4 mapping already resolves these classes.

## Data and capability changes

- Hub schema version is now `1.1.0`, adding nullable case owner, response/resolution due timestamps, first-response timestamp, SLA state, and pause timestamp with queue indexes. `dbDelta()` migration runs when the saved schema version differs.
- New REST routes are under `wperh/v1/pro/` and `wperh/v1/cases/{id}/assignment`; settings require `manage_woocommerce`, case operations require order-management capability, and assignment verifies the target user's `edit_shop_orders` capability.
- Alerts contain case/order IDs and an admin link only. Customer addresses, email addresses, payment tokens, and gateway payloads are not copied into Pro event records.

## Pro boundary and current limitations

- Pro source is in the development repository, but the standard development/free bootstrap does not define `WPERH_PRO_EDITION`. A future paid package must include the Pro source and a reviewed activation/licensing mechanism. This phase does not implement license issuance, billing, update delivery, or a final ZIP split.
- The Pro alert channel is administrator email only. Customer notifications, SMS, webhook/third-party delivery, and a separate in-app notification center are outside this phase.
- Workflow conditions use AND semantics only. The rule builder is bounded to the documented triggers and internal actions; it is not a general automation engine.
- Approval safeguards are enforced by excluding all high-impact order-action execution. A future action proposal/approval/execute lifecycle requires its own review and implementation before any such action is introduced.
- Trend reporting, CSV export, saved views, and bulk tools are not part of the Phase 6 implementation; README options were adjusted to keep these as later/release decisions.

## Verification evidence

- Repository source and the affected free/pro REST, service, schema, UI, and settings paths were reviewed during implementation.
- No PHP runtime, WordPress/WooCommerce install, HPOS mode, mail transport, accessibility, performance, or marketplace compatibility checks were run. These belong to Phase 7 and the distribution phases.
- No CLI task, CLI command, or CLI work item was added.

