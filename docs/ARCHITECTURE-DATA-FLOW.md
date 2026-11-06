# Architecture and data-flow specification

**Status:** Phase 3 complete — architecture baseline approved for pre-development (2026-11-06).  
**Scope:** Design decisions and data contracts only. No plugin implementation or runtime behavior has been built.

## 1. Architecture summary

The plugin is a WordPress/WooCommerce add-on with a PHP application layer, WooCommerce CRUD integration, plugin-owned case/audit storage, WordPress REST endpoints for the React admin UI, and opt-in Pro services for internal alerts, assignment, SLA and bounded workflows.

WooCommerce remains authoritative for orders, line items, payment, stock, customer, and shipping data. Order Rescue Hub stores only the Hub case state needed to coordinate resolution and a redacted record of Hub activity.

| Layer | Responsibility | Boundary |
|---|---|---|
| Bootstrap and Composer autoloader | Load namespaced plugin classes and guard dependencies | Merchant server does not need Composer installed |
| WooCommerce adapter | Read orders through supported CRUD/query APIs; translate relevant signals | No direct read/write of WooCommerce order storage |
| Detection and case services | Classify exceptions, create/update cases, validate transitions and idempotency | Detection never mutates order/payment status |
| Hub repository | Persist case and event records in Hub-owned tables | No full order/customer/payment payloads |
| REST controller | Authenticate, authorize, validate, paginate and serialize UI requests | A REST nonce alone is not authorization |
| React admin UI | Present queue, evidence, actions and outcomes | Not a second source of truth for orders |
| Pro services | Admin alerts, assignment, SLA and bounded rules | No customer messages or financial actions |

## 2. Data ownership and schema

**Decision:** Use plugin-owned tables for case coordination and event history. Do not store case lifecycle in WooCommerce order metadata. Queue and history need bounded indexed queries independent of order storage, and Hub events are not WooCommerce order facts.

Use the current site table prefix with stable suffixes:

- {site-prefix}wperh_cases
- {site-prefix}wperh_case_events

Create and update tables through WordPress’s supported schema-upgrade mechanism (dbDelta) and a plugin schema-version option. Migrations must be repeatable, forward-only, bounded and preserve existing data. Destructive migrations require a documented backup and rollback plan. Validate exact column types, index sizes, charset/collation, and InnoDB behavior against the Phase 7 MySQL/MariaDB test matrix.

### Cases table

One long-lived case per WooCommerce order. Multiple reason codes can be attached to a case. A new qualifying exception after closure reopens the case as a new episode and records that transition.

Planned fields:

- case_id: unsigned bigint primary key.
- order_id: WooCommerce order ID, unique reference only.
- state: Hub lifecycle state, separate from order status.
- reason_codes: JSON text list of canonical reason codes.
- evidence: compact JSON text with allow-listed, non-sensitive facts and observation times.
- priority: bounded value; the explanation is recorded in the event history.
- assignee_id: nullable WordPress user ID; assignment does not grant order access.
- first_detected_at, last_detected_at, next_review_at, sla_due_at, closed_at, created_at, updated_at: UTC timestamps.
- episode: monotonically increasing reopen cycle.
- row_version: optimistic concurrency marker.

Indexes: primary key; unique order_id; queue index by state/priority/update time; assignment/state index; SLA due/state index.

### Case events table

Append-only Hub activity history.

Planned fields:

- event_id: unsigned bigint primary key.
- case_id: case reference.
- idempotency_key: deterministic fixed-length key, unique, for detection, transitions, alerts and retries.
- event_type: allow-listed event type.
- actor_id: nullable user ID; null for system events.
- occurred_at: UTC timestamp.
- payload: small JSON text of event-specific allow-listed values. Private note text is personal data and must follow retention/privacy controls.

Indexes: primary key; unique idempotency key; case/time; event type/time. Normal application behavior appends events. Retention cleanup is an explicit maintenance action with an audit event.

### Pro configuration and run records

Small Pro rule definitions and settings belong in validated, versioned WordPress options. Rule execution, notification delivery, assignment and SLA changes use the event history with rule ID/version and redacted result codes. Add a separate indexed run table only after measured volume justifies it and a migration decision is recorded.

Do not persist full order snapshots, addresses, emails, payment tokens, gateway payloads, or product/customer objects. Store the order ID and reason codes; fetch the current order snapshot through WooCommerce APIs when requested and show when it was observed. Evidence has a strict allow-list and is redacted before persistence/logging.

## 3. Detection and event processing

1. **Immediate signals:** Supported WooCommerce order-created/updated/status/payment/stock-related hooks mark an order for re-evaluation. Hooks stay lightweight and make no slow external requests or unbounded scans.
2. **Re-evaluation:** A bounded worker re-fetches the order through WooCommerce CRUD APIs, evaluates enabled reasons, and creates or updates the case. Hook payloads are not trusted as current payment/order truth.
3. **Age checks:** A recurring WordPress scheduled event performs a paginated, bounded sweep of eligible orders with wc_get_orders / WC_Order_Query. A short-lived lock, batch cap, progress cursor, and backoff prevent overlap and expensive runs. This is an eventual catch-up mechanism, not a real-time SLA guarantee.
4. **Case actions:** REST actions validate the current case version and current order access, then persist the state change and attributed event as one logical operation. Conflicts return a refreshable stale-case response.

Schedule interval, batch size, stale-lock recovery, and supported WooCommerce hooks are implemented and measured in Phases 5 and 7 against the target platform matrix. WordPress scheduled events are traffic-driven and may run late on low-traffic sites; the UI must display observation and last-run times accurately.

### Classification rules

- Pending payment becomes actionable only after an explicit grace period; respect payment method behavior and do not classify legitimate offline payment as failed.
- Failed payment uses current WooCommerce status/payment details and a fresh recheck.
- Missing shipping data applies only when an order requires shipping; virtual/non-shipping orders are excluded.
- Processing delay uses a configurable age threshold and state-aware exclusions.
- Stock discrepancy checks relevant order items through product/variation stock APIs. The Hub does not reserve or change stock.
- Deleted/unavailable orders retain Hub history, show an unavailable reference, and cannot receive order mutations.
- Every reason includes a reason code, observed time, evidence source, and next-step hint.

### Idempotency and concurrency

- Build stable event keys from site, order ID, event family, source/version and occurrence identity; never put customer data in keys.
- Unique event keys suppress duplicate history and alerts on retries.
- One unique case row per order merges current reasons/evidence; a later signal can reopen a closed case as a new episode.
- Compare row_version on writes. On conflict, reload and re-evaluate rather than overwrite another staff member’s change.
- Notification deduplication uses case/event/channel/recipient; record attempted, delivered, skipped or failed outcomes without storing full message bodies.
- Any future high-impact order action requires a separate propose/approve/execute record, fresh order read, capability and nonce validation, idempotency, explicit confirmation, and durable audit. It remains outside MVP and cannot be triggered by rules.

## 4. Data flow

1. A WooCommerce signal or scheduled age sweep identifies a candidate order.
2. The adapter re-fetches the order through wc_get_order and eligible candidates through wc_get_orders; the classifier evaluates supported exception rules.
3. The case service writes allow-listed Hub data and an idempotent event. Detection does not mutate the order.
4. React requests paginated, permission-filtered data through the plugin REST namespace. The response contains only fields needed for the current view.
5. A staff action is authorized and validated server-side, then updates Hub state and creates an attributed event.
6. Pro alert/SLA logic consumes the committed case event and records the outcome. Delivery failure does not roll back the case or staff action.

## 5. REST, permissions and security

Use a versioned namespace such as wperh/v1. Define explicit routes for paginated case collection, case detail/history, private note, transition, and Pro assignment/settings. Do not expose generic arbitrary metadata or action endpoints.

- Require authenticated users and a valid REST nonce for cookie-authenticated admin requests.
- Check the relevant capability on every request; nonce validation is CSRF protection, not authorization.
- Separate capabilities for reading cases, managing cases, assigning cases and managing settings. Map defaults conservatively to existing WooCommerce order-management roles; Hub assignment does not grant order access.
- Before returning order-linked data or allowing an action, check the user’s current capability for that specific order through supported WooCommerce permission helpers.
- Validate all transitions server-side against the canonical state graph. Reject stale row_version values.
- Sanitize and limit note/settings input; escape output; use prepared database operations; validate REST schemas and permission callbacks.
- Paginate with default page size 25 and hard maximum 100, stable sorts, and allow-listed filters. Avoid unrestricted search, arbitrary SQL/order fields, unbounded order loading and N+1 requests.
- Return user-safe errors; log only correlation ID, case ID, reason code and redacted failure class. Never log payment payloads, addresses, email, note contents, nonce, credentials or full REST bodies.
- Rate-limit exports and bulk case operations. Re-check authorization for every bulk target.
- Initial release is per-site for multisite; network-wide setup/reporting is out of scope.

## 6. Retention, privacy and uninstall

- Open cases are not removed by time-based retention.
- Proposed default: closed cases and related history are retained for 12 months after closure, then deleted in bounded batches. Settings can shorten or extend this within documented limits; reconfirm the default against applicable privacy/support requirements before release.
- Private notes are user-generated personal data. Include Hub notes and case records in applicable WordPress privacy exporter/eraser integration while respecting order access and data-rights rules.
- On plugin deletion, retain Hub tables by default to avoid accidental loss of operational history. Provide a clear setting to purge Hub-owned data on deletion; this destructive setting is off by default.
- Explicit purge or erase requests remove only Hub-owned data, never WooCommerce orders.
- Document retention, privacy and uninstall behavior before directory submission.

## 7. Failure handling

| Failure | Required behavior |
|---|---|
| WooCommerce unavailable | Plugin stays inert with an admin dependency notice; no fatal or order query |
| Order deleted/unavailable | Preserve case history, flag snapshot unavailable, disable order-specific operations |
| Duplicate hook/retry | Unique idempotency key suppresses duplicate event and alert |
| Lock conflict/overlapping sweep | Exit safely, record a redacted skipped result, retry later |
| Invalid/stale update | Return conflict and refresh guidance; do not overwrite newer state |
| Database write failure | Do not report success; show actionable error and safe correlation ID |
| Notification failure | Preserve case/action; record and retry delivery within bounded dedupe rules |
| Partial migration/outdated schema | Stop unsafe writes, preserve data, show recovery notice |
| Unknown reason/status | Fail closed for actions and retain a safe diagnostic code |
| Permission changes after page load | Recheck server-side; deny mutation and protected order data |

Recovery remains available in WordPress admin; no CLI-only recovery or merchant setup is planned.

## 8. Composer PSR-4 autoloader

**Required namespace prefix:** WPEssential\OrderRescueHub\  
**Required mapping:** WPEssential\OrderRescueHub\ maps to includes/.

Declare this mapping in the project Composer manifest and ship the Composer-generated runtime loader (vendor/autoload.php and required generated metadata) in the production package. The plugin bootstrap loads it from the plugin directory after a direct-access guard and before initializing namespaced services. The merchant runtime must not require Composer to be installed or invoke a command. Keep development-only packages out of the release artifact and review shipped package licenses.

Use the Composer-generated autoloader as the single class loader; do not add a parallel hand-written loader or per-class include list. This records the architecture only. It does not create a Composer command/backlog item or authorize CLI work before final plugin readiness.

## 9. Phase 3 acceptance checklist

- [x] Case and append-only event data ownership selected.
- [x] Proposed fields, indexes, idempotency and reopen semantics documented.
- [x] WooCommerce event, age-sweep, classification and case-action data flows documented.
- [x] HPOS-safe CRUD/query boundary retained.
- [x] REST, pagination and role/capability model documented.
- [x] Retention, privacy export/erase, uninstall and multisite boundaries documented.
- [x] Failure and recovery behavior documented.
- [x] Composer PSR-4 namespace prefix, source mapping and shipped runtime autoloader specified.
- [x] No CLI task/work item added.

**Evidence:** This document plus updated architecture decisions, README roadmap and status log.  
**Not performed in Phase 3:** No PHP/React implementation, schema migration, Composer-generated artifact, CLI task, automated test, compatibility test, or live-store operation.

## 10. Official engineering references

Reviewed 2026-11-06; recheck before implementation and release.

- WooCommerce HPOS extension recipe book: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/
- WooCommerce order querying: https://developer.woocommerce.com/docs/features/orders/wc-get-orders/
- WooCommerce HPOS query API guidance: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/wc-order-query-improvements/
- WordPress plugin custom table guidance: https://developer.wordpress.org/plugins/creating-tables-with-plugins/
- Composer PSR-4 autoloading and generated runtime loader: https://getcomposer.org/doc/01-basic-usage.md#autoloading
- Composer autoloader optimization: https://getcomposer.org/doc/articles/autoloader-optimization.md
