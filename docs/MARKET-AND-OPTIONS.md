# Market scan and canonical options

**Research checked:** 2026-11-06. This is a public-source feature scan, not hands-on testing or customer research. Marketplace copy is treated as the vendor's description, not an independently verified product claim. Pricing, package requirements, and features must be rechecked before launch.

## Competitor and adjacent-product comparison

| Product | Publicly documented overlap | Price signal | Decision for Order Rescue Hub |
|---|---|---|---|
| WooCommerce core Orders | Order list/detail, payment/status information, order notes, manual actions, hold-stock behavior and troubleshooting | Core | Integrate with native order screens/APIs; do not recreate the order editor or infer that every Pending/On hold order is broken |
| WooCommerce Order Workflows | Custom statuses, allowed transitions, condition-based auto-status rules, status emails, transition history, filters and CSV export | Marketplace price not confirmed in this scan | Do not build another order-status engine. Keep cases separate from order statuses; link staff to the native order |
| Stale Order Alerts for WooCommerce | Daily check of Processing/On hold orders against age thresholds; email and dashboard banner; manual recheck/test email; HPOS compatibility | Free directory listing | Alerts are not sufficient differentiation. Link an alert to an actionable case with evidence, owner and outcome |
| Failed Order Rescue for WooCommerce | Dashboard for failed/pending orders, manual payment-link email, email customization, ignore controls and recovered-revenue tracking; paid recovery features are described as planned | Free edition; planned Pro price not published | Payment recovery is an optional reason/next-step suggestion, not the whole product. No unsolicited customer email in the MVP |
| Order Priority for WooCommerce | Rule-based priority levels, multiple conditions, order-table badges/filters, bulk priority update, manual override and order-note audit | Marketplace price not confirmed in this scan | Use explainable priority as a triage field; do not become a priority-label-only tool |
| Assign Orders for WooCommerce | Manual and rules-based assignment, assignment emails, assigned-order views, access restrictions | $79/year shown on the Marketplace listing when checked | Assignment is valuable but insufficient alone. Preserve existing WordPress capabilities; do not silently change order access permissions |
| Universal Workflow Automation for WooCommerce | Broad visual automation: many triggers, actions and conditions, templates, retries, execution history, dry-run and import/export; includes order, email, stock and refund actions | Free edition; optional Pro price not confirmed | Explicitly avoid a generic visual automation builder. Limit Hub rules to exception triage, assignment and internal alerts |
| Out The Door — Order Tasks & Workflows | Order-linked tasks, priority, due dates, task status, Kanban/calendar views, custom order statuses and partial-shipment workflow | Free directory listing; pricing not needed for this scope decision | Treat this as adjacent task management. Hub cases must be generated from order exceptions and include reason/evidence/age/resolution |

### Findings and product gap

The market is not empty: workflow rules, status transitions, alerts, failed-payment reminders, priority, assignment, and order-linked tasks each exist in adjacent tools. A feature checklist alone would not make this product distinct.

**Chosen positioning:** an exception-resolution workspace. Its core record is a *case* linked to an order and a specific exception reason, with age, evidence, next action, owner/SLA where enabled, and a documented outcome. WooCommerce order status remains the order's payment/fulfilment state; a Hub case state is not a replacement order status.

**Naming rule:** use **WPEssential Order Rescue Hub** as the distinctive product name and use “for WooCommerce” only as a compatibility descriptor in the marketplace-style suffix. Do not use Woo/WooCommerce as the product brand, company identity, or domain. Keep the repository slug requested by the owner; treat it separately from product branding and confirm the final listing copy against the current trademark rules.

**Product boundaries:**

- Pending and On hold are not automatically errors. Payment method, gateway notes, stock reservation and expected confirmation windows affect whether an order is genuinely stuck.
- No general-purpose workflow canvas, custom order-status editor, shipping label system, payment gateway, or customer-support conversation tool.
- No automatic customer email in the first release. Recovery links may be presented as an operator's next step only if the gateway/native order API exposes them safely.
- Payment capture, refunds, cancellation and payment-implying status changes are not routine workflow actions. If approved for a later version, require per-action approval, fresh state validation, capability/nonce checks, idempotency, and an audit result.
- Assignment organizes case ownership; it does not grant staff access they do not already have in WordPress/WooCommerce.

## Canonical options and duplication policy

The README's **Unified options bank** is the canonical capability list. One key owns each user-visible capability. Conditions inside a capability (for example age thresholds or AND/OR workflow matching) are not separate options with duplicate names.

Research-driven additions and clarifications recorded in the README:

- Separate detection thresholds (`exception.detection`) from staff-selected age filters (`exception.age_filters`).
- Separate the case lifecycle (`case.state`) from its terminal resolution reason (`resolution.outcome`).
- Keep current queue controls together under `exception.inbox`; safe bulk case operations have their own `queue.bulk_tools` capability.
- Keep alert channels, test alert and deduplication policy under `alerts.channels`.
- Keep workflow trigger/condition/priority/pause/dry-run/run-history controls under `workflow.rules`; do not duplicate these as independent automation settings.
- Keep internal notes distinct from customer-facing order notes and emails. No customer communication feature is part of the MVP.

## Distinct systems bank

These are system responsibilities, not duplicate UI settings:

1. **Exception detection:** WooCommerce lifecycle events plus bounded age checks; explain each reason and avoid false positives.
2. **Case identity/idempotency:** At most one active case per order/reason/rule identity; repeated hooks and retries must not create duplicate cases.
3. **Case lifecycle:** Open, triaged, assigned, waiting, escalated, resolved/dismissed; preserve transition actor, timestamp and source.
4. **Queue/query:** Paginated WooCommerce-supported order reads, stable sorting, searchable/filterable reasons and cases; no direct order-table reads.
5. **Prioritization:** Deterministic, explainable priority; use age/SLA/reason inputs without reimplementing WooCommerce order status.
6. **Assignment:** Case owner and reassignment history; no privilege expansion or implicit order ACL changes.
7. **SLA clock:** Explicit timezone, business/calendar time choice, pause states, due/breach behavior and safe recalculation after policy edits.
8. **Notification delivery:** Admin-only channels initially, throttling/deduplication and delivery outcome; no default external service calls.
9. **Bounded workflow evaluator:** Versioned, inspectable rules, rule priority, dry-run, pause, idempotent execution and retry limits; only approved low-risk case actions by default.
10. **Approval/action gateway:** Keep proposal separate from execution; fresh order/payment-state check, explicit per-order approval, idempotency and append-only audit before any approved high-impact action.
11. **Storage and migrations:** Store case data separately from WooCommerce order truth; schema versioning, upgrade, retention, export/delete and uninstall behavior documented before implementation.
12. **Access, privacy and audit:** Existing WordPress capabilities/nonces, least privilege, input/output safety, minimized personal data, redacted logs and attributable case history.
13. **Localization and compatibility:** WordPress translation APIs for PHP/React strings; HPOS and supported classic order storage tests; WPML/Polylang test matrix without hard dependency on either.
14. **Performance and observability:** Bounded queries, indexed plugin-owned case fields if justified, cache invalidation, actionable admin errors and no payment/customer payloads in routine logs.

## Phase 1 completion record

- [x] Compared core plus seven direct/adjacent product classes using public feature documentation/listings.
- [x] Recorded product overlap, market gap, differentiation and explicit non-goals.
- [x] Expanded the canonical option bank and documented key ownership to avoid duplicate settings.
- [x] Separated product options from system responsibilities.
- [x] Recorded supported-version baseline and multilingual test targets in `docs/PLATFORM-BASELINE.md`.
- [x] Added both distribution tracks and their policy gates to `docs/DISTRIBUTION-PLAN.md`.
- [x] Deferred customer willingness-to-pay, Woo Marketplace listing-model confirmation and changing release-time versions to explicit future gates; they do not block this requirements phase.

## Sources

- WooCommerce order troubleshooting: https://woocommerce.com/document/managing-orders/troubleshooting-orders/
- WooCommerce order statuses: https://woocommerce.com/document/managing-orders/order-statuses/
- WooCommerce Order Workflows docs: https://woocommerce.com/document/order-workflows/
- WooCommerce Order Workflows Marketplace listing: https://woocommerce.com/products/order-workflows/
- Stale Order Alerts: https://wordpress.org/plugins/stale-order-alerts-for-woocommerce/
- Failed Order Rescue: https://wordpress.org/plugins/failed-order-rescue-for-woocommerce/
- Order Priority: https://woocommerce.com/products/order-priority/
- Assign Orders docs: https://woocommerce.com/document/assign-orders/
- Assign Orders Marketplace listing: https://woocommerce.com/products/assign-orders/
- Universal Workflow Automation: https://wordpress.org/plugins/universal-workflow-automation/
- Out The Door: https://wordpress.org/plugins/out-the-door-order-tasks-workflows-for-woocommerce/
- WooCommerce HPOS recipe book: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/
- Woo trademark guidelines and compatibility naming: https://woocommerce.com/trademark-guidelines/
- Woo Marketplace product naming style: https://developer.woocommerce.com/docs/woo-marketplace/content-style-guide
