# Product and UX specification

**Status:** Phase 2 complete — specification approved for architecture handoff (2026-11-06).  
**Scope:** Product behavior and interface specification only. This document does not claim that the React UI or plugin was implemented during Phase 2; Phases 5–6 implementation status and limitations are recorded in the implementation reports.

## 1. Product promise and users

Order Rescue Hub gives store staff one place to find WooCommerce orders with a specific operational exception, understand why each case needs attention, assign the next action, and record the outcome. WooCommerce remains the source of truth for orders, payment, stock, customers, and fulfillment.

### Primary users

- **Store owner / administrator:** configures exception thresholds and Pro workflows; reviews unresolved and breached cases.
- **Order manager:** scans, triages, assigns and resolves exceptions within existing WordPress/WooCommerce permissions.
- **Support / fulfillment staff:** handles assigned cases, adds private notes, and marks the case waiting or resolved.
- **Read-only reviewer:** views cases and history only when granted the relevant capability.

No role receives order access merely because it is assigned a case. Authorization for order data and all order actions continues to use existing capabilities.

### Jobs to be done

1. Find the cases that need attention now, without treating every Pending or On hold order as broken.
2. See the order facts and evidence behind an exception before choosing a next step.
3. Record who handled the case, what they found, and how the exception ended.
4. For Pro, route alerts, assignment, and SLA escalation predictably, with visible rule history.

## 2. Information architecture

Use a single **Order Rescue Hub** entry within the existing WooCommerce administration area, subject to Phase 3 confirmation against the latest extension menu guidance. Do not add a competing top-level WordPress menu.

| Area | Purpose | Edition |
|---|---|---|
| Exceptions | Primary queue, saved views, filters and case detail | Free + Pro |
| Reports | Basic totals in the queue; trends and export in a dedicated view | Basic Free / expanded Pro |
| Workflows | Bounded internal triage, assignment and admin notification rules | Pro |
| Team | Case assignment and reassignment | Pro |
| Settings | Detection thresholds, alert delivery, SLA policy, data/privacy and edition information | Shared sections, Pro controls gated |

The exact route and settings placement will follow the supported WordPress/WooCommerce version and current design guidance. The application must remain useful if Pro is absent or inactive; Pro controls should explain their availability without blocking Free workflows.

## 3. Exception inbox

### Page structure

1. Page title and one-sentence purpose.
2. Compact summary cards: open, unassigned, overdue (Pro), resolved in selected period. Cards are links to the corresponding filtered queue, not decorative charts.
3. Search by order number and permitted customer fields.
4. Filter bar: reason, case state, age/date range, priority, owner (Pro), SLA state (Pro), and saved view (Pro).
5. Result count and active filter chips with a clear-all action.
6. Paginated results table.
7. Row selection and safe case-only bulk actions where Pro is enabled.

### Queue columns

| Column | Behavior |
|---|---|
| Order | Order number links to the WooCommerce order using a new tab only when the user chooses; show date/time in the store timezone |
| Exception | Human-readable reason plus concise evidence, with multiple reasons summarized and accessible in detail |
| Age | Time since the exception first became actionable; never reset silently on re-detection |
| Priority | Explainable level; include text label and reason, not color alone |
| Case state | Hub case state, explicitly distinct from WooCommerce order status |
| Owner | Pro assignee or “Unassigned” |
| SLA | Pro due time and state; hide column and related filters in Free |
| Updated | Last Hub case activity, separate from order modification time |
| Actions | Open case; no one-click financial or order-status action |

Default sort: highest priority, then oldest actionable age. User sort selection should be stable while paging. Empty filter results must retain the filters and show a useful reset action. Use server-side pagination and bounded searches; do not load an unbounded order list into React.

### Canonical case states

Open → Triaged → Assigned → Waiting → Escalated → Closed

- Assigned is only used when an owner exists.
- Waiting requires an optional private reason and next-review date, if configured.
- Escalated is available to Pro when the escalation rule fires or an authorized staff member escalates manually.
- Closed requires a resolution outcome: Resolved, Dismissed, Duplicate, or No action required.
- Reopening creates a history event and records who reopened it.
- These states never change the WooCommerce order status.

## 4. Case detail

Open a dedicated case page or accessible side panel from the queue; Phase 3 will decide based on focus management, browser navigation, and available WordPress components. The detail view must include:

- **Case header:** order number, case state, reason, priority, owner, age, SLA status if Pro.
- **Why flagged:** specific rule/reason, detection time, observed evidence, and a clear “last checked” timestamp.
- **Order snapshot:** only the order information required for triage (status, payment method/status, totals, line items/stock context, shipping completeness, order date). Link to the native WooCommerce order screen for the full record.
- **Recommended next step:** explanatory guidance only; distinguish suggestion from an executed action.
- **Case controls:** state transition, assign/reassign (Pro), add private note, set waiting reason/review date, close with outcome, reopen if permitted.
- **Activity history:** chronological, attributed entries for detection, changes, notes, rule runs, alerts, SLA transitions and outcomes. Distinguish system events from staff actions.
- **Order-action boundary:** no capture/refund/cancel/payment-implying status control in MVP. Any later approved action must show the exact order and consequences, require a fresh confirmation and permission check, and create an audit result; workflow rules must not bypass this gate.

Notes are internal Hub notes. UI copy must not imply they are WooCommerce customer-facing order notes or trigger email.

## 5. Workflows, team and SLA (Pro)

### Workflow builder

Keep rules bounded to exception cases and internal operations. A rule comprises: enabled/paused state, name, trigger, conditions, actions, priority, dry-run mode, and version/history.

- Triggers: a supported exception opens, its reason or case state changes, an SLA approaches or breaches, or it matches a scheduled age check. Case-change triggers are deduplicated per rule and case episode to prevent recursive workflow loops.
- Conditions: reason, age band, case state, priority, store order properties available through supported APIs.
- Actions: set case priority, assign an eligible staff member, change case state where valid, and send an administrator notification.
- Show a readable “when / if / then” summary before saving.
- Validate incompatible or looping transitions; prevent a rule from triggering itself indefinitely.
- Dry-run previews expected matches and proposed internal actions; it never executes order payment or cancellation actions.
- Rule history records version, trigger result, skipped/retried state, and a redacted failure reason. Provide pause/disable controls and retry limits.

### Assignment

Users can be assigned only if eligible under the configured capability policy. Reassignment records previous owner, new owner, actor and time. Notifications are opt-in/admin-configured and deduplicated. Assignment does not grant access to orders or private customer data.

### SLA policy

Admin-configurable response/resolution targets, store timezone, calendar-hours versus business-hours mode, working days and holidays (if business-hours mode is selected), pause conditions, warning threshold, and breach behavior. UI must show which clock is running and why it is paused. Defaults remain conservative and are not represented as a guarantee to customers.

## 6. Settings and option bank mapping

Settings are grouped by task, not duplicated across pages:

1. **Detection:** exception types, thresholds, grace periods, exclusions and last evaluation.
2. **Queue:** default sort, visible columns and saved views (Pro).
3. **Alerts:** administrator recipients/channels, digest/frequency, throttle, test delivery and last delivery result (Pro).
4. **Team and SLA:** eligible staff, timezone, hours, targets, pause and escalation policies (Pro).
5. **Workflow safety:** dry-run default, enabled rules, action boundary and history retention (Pro).
6. **Data and privacy:** Hub-data retention, export/erase support, uninstall behavior summary.
7. **About and compatibility:** edition, version, dependency status, documentation/support links and non-intrusive upgrade information.

Each control maps to an existing canonical capability in the README option bank. Do not add a second setting for the same concept. Settings use explicit defaults, plain-language descriptions, inline validation, save confirmation, and a recoverable error state. External services and customer-facing communication are not enabled by default.

## 7. Shared interaction and visual system

- React application embedded in WordPress admin; use the WordPress-provided React/runtime and current supported WordPress/WooCommerce components where appropriate.
- Follow WordPress/WooCommerce page structure, labels, spacing, controls, notices and admin color scheme. No separate branded dashboard shell, custom navigation system, or decorative marketing dashboard.
- Use progressive disclosure: the queue shows only triage-critical facts; full evidence and history are in case detail; advanced rules stay in Pro settings.
- Use consistent verbs: **Review case**, **Assign**, **Add private note**, **Mark waiting**, **Close case**, **Reopen case**. Do not label a case closure as an order completion.
- Show dates in the store timezone and include timezone context where a deadline may be ambiguous.
- Provide loading skeleton/announcement, empty state, first-run state, filtered-empty state, permission denial, dependency missing, stale order snapshot, save success, validation, network/server failure, and partial alert-delivery states.
- On stale data, explain when the snapshot was checked and require refresh before any future sensitive action.
- Provide a narrow-layout fallback for the admin viewport: filters collapse accessibly, table rows reflow into labeled summaries, and controls remain operable without horizontal page scrolling.

## 8. Accessibility and localization

Target WCAG 2.2 AA and WordPress/WooCommerce accessibility conventions.

- All actions and filters work by keyboard; visible focus order is logical and focus is restored after dialogs/panels close.
- Use semantic headings, table headers, labels, field descriptions, button names and status announcements. Announce queue refreshes and save results without unexpectedly moving focus.
- Meet contrast requirements in all admin color schemes; do not use color alone for reason, priority, SLA or state.
- Dialogs and side panels must have correct labeling, focus containment when modal, Escape behavior where safe, and return focus to the launching control.
- Respect reduced-motion settings; do not use animation as the only status signal.
- Keep strings translation-ready with WordPress internationalization conventions. Use locale-aware dates/numbers and avoid constructing sentences from concatenated fragments. Preserve RTL layout behavior.
- WPML and Polylang remain compatibility requirements; language context may be shown for an order only when safely available.

## 9. Phase 2 acceptance checklist

- [x] Product users, jobs and boundaries described.
- [x] Navigation and page inventory defined.
- [x] Inbox data, filters, sorting, pagination, states and safe bulk-action scope specified.
- [x] Case detail, private notes, evidence, outcomes and audit display specified.
- [x] Pro workflow, assignment, alert and SLA behavior bounded and described.
- [x] Empty, loading, permission, stale-data, validation and failure states covered.
- [x] React/WordPress design-system direction specified.
- [x] Accessibility, localization, RTL and responsive admin behavior specified.
- [x] Canonical option bank reuse and safety boundary preserved.
- [x] PSR-4 autoloader requirement handed to architecture and implementation plan.
- [x] CLI tasks remain excluded until the plugin is final.

**Evidence:** This specification and updates to README phase tracking, architecture decisions, AI handoff backlog and status log.  
**Not performed in Phase 2:** No React screens, PHP implementation, CLI tasks, tests, or live-store operations.

## 10. Official design references

Reviewed 2026-11-06; recheck against the supported WordPress/WooCommerce matrix during implementation because component APIs and Marketplace guidance can evolve.

- WooCommerce extension UX guidelines: https://developer.woocommerce.com/docs/extensions/ux-guidelines-extensions/
- WooCommerce extension best practices: https://developer.woocommerce.com/docs/extensions/ux-guidelines-extensions/best-practices
- WooCommerce settings and admin page guidance: https://developer.woocommerce.com/docs/extensions/settings-and-config/
- WordPress Components package: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-components/
- WordPress accessibility coding standards (WCAG 2.2 AA): https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/
- WordPress plugin admin menu guidance: https://developer.wordpress.org/plugins/administration-menus/
