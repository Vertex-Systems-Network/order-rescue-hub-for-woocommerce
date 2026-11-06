# WPEssential Order Rescue Hub for WooCommerce

**Repository slug:** `wpessential-order-rescue-hub-woocommerce`  
**Product type:** WPEssential add-on for WooCommerce  
**Development status:** Free MVP and Pro capability source complete; package and release validation pending  
**Current release:** 0.1.0 development package (not a production release)

> A focused workspace for finding, prioritizing, assigning, and resolving WooCommerce order exceptions.

## Product direction

WooCommerce stores can leave orders needing attention across payment, shipping, stock, and fulfillment states. Staff currently have to inspect order lists, order notes, gateway details, and shipping information separately. Order Rescue Hub is planned as one exception-focused workspace that makes the next human action clear and records how the case was handled.

This is not intended to replace WooCommerce order management, a general workflow builder, or a payment gateway. Its core value is an explainable exception queue with safe resolution tracking.

### Planned editions

| Edition | Planned scope |
|---|---|
| Free | Stuck-order dashboard, age-based filters, exception reasons, search, manual resolution notes, basic audit history |
| Pro — $99/year (proposed) | Automated alerts, configurable workflows, team assignment, SLA tracking, richer reporting and history |

Price and packaging are planning assumptions and require product validation before launch.

### Safety boundary

The plugin may recommend or prepare a resolution. Payment capture, refunds, cancellations, status changes that imply payment, and other financially consequential actions must never run silently. Any such action requires an explicit capability check, clear order-specific summary, user confirmation, idempotency protection, and an auditable result. Default automation must be read-only or notification-only until an administrator deliberately enables an approved workflow.

## Planning status

Progress is tracked from reviewable deliverables. Planned dates are estimates and will be revised as each phase is scoped.

| Phase | Deliverable | Status | Progress | Start | Target end |
|---|---|---:|---|---|---|
| 0. Product framing | Problem, audience, boundaries, free/pro hypothesis | Complete | `█████ 100%` | 2026-11-06 | 2026-11-06 |
| 1. Market and requirements | Competitor map, canonical option/system banks, product gap, version baseline and distribution requirements | Complete | `█████ 100%` | 2026-11-06 | 2026-11-06 |
| 2. Product and UX specification | Jobs-to-be-done, information architecture, React admin UI flows, accessibility requirements | Complete | `█████ 100%` | 2026-11-06 | 2026-11-06 |
| 3. Architecture and data flow | Case/event schema, detection flow, REST permissions, retention, failure handling, Composer PSR-4 autoloader design | Complete | `█████ 100%` | 2026-11-06 | 2026-11-06 |
| 4. Pre-development package | Technical decisions, acceptance criteria, threat model, release and test matrix | Complete | `█████ 100%` | 2026-11-06 | 2026-11-06 |
| 5. Free MVP implementation | Exception inbox, filters, notes, audit history, accessible admin UI, bundled Composer PSR-4 autoloader | Complete | `█████ 100%` | 2026-11-06 | 2026-11-06 |
| 6. Pro capabilities | Admin email alerts, bounded workflows, team assignment, SLA clocks and approval boundary | Complete | `█████ 100%` | 2026-11-06 | 2026-11-06 |
| 7. Compatibility and quality | WordPress/WooCommerce matrix, HPOS, WPML, Polylang, security, performance | In progress — static audit and fixes; runtime gates pending | `██░░░ 35%` | 2026-11-07 | 2027-01-08 |
| 8. WordPress.org Free submission | GPL-compatible Free package, readme/assets, directory review submission and SVN release preparation | Not started | `░░░░░ 0%` | 2027-01-09 | 2027-01-22 |
| 9. WooCommerce.com Pro submission | Vendor application, paid Pro package, QIT/UX evidence, Marketplace review and feedback fixes | Not started | `░░░░░ 0%` | 2027-01-23 | 2027-02-05 |
| 10. Release and maintenance readiness | Approval-dependent launch checklist, versioned releases, support and update process | Not started | `░░░░░ 0%` | 2027-02-06 | 2027-02-12 |

**Overall:** Phases 0–6 are complete for product framing, requirements, specifications, architecture, pre-development, Free implementation, and Pro capability source. Phase 7 static source audit has started; runtime compatibility and release validation remain open. See [the Phase 7 quality report](docs/PHASE-7-QUALITY-REPORT.md). The Pro source is gated for paid packaging; no license or Marketplace approval is claimed. Dates are provisional, not delivery commitments.

## Phase 5: Free MVP implementation

**Status: complete (100%, 2026-11-06).** The Free implementation, React admin inbox, REST endpoints, case/event storage, Composer PSR-4 runtime, and developer package notes are recorded in [docs/IMPLEMENTATION-FREE-MVP.md](docs/IMPLEMENTATION-FREE-MVP.md). JavaScript syntax was checked with Node. PHP parsing/runtime, WordPress/WooCommerce install, HPOS, multilingual behavior, security, accessibility, and performance validation have not been run; these remain Phase 7 release-validation gates. No CLI work item was added.

## Phase 0: product framing

**Status: complete (100%, 2026-11-06).** The target user, order-exception problem, WPEssential add-on positioning, Free/Pro hypothesis, payment-action safety boundary and product non-goals are recorded in [docs/PRODUCT-SCOPE.md](docs/PRODUCT-SCOPE.md) and this README.

## Phase 1: market scan and product gap

**Status: complete (100%, 2026-11-06).** The source-backed competitor comparison, canonical options/systems, version baseline, distribution gates and deferred decisions are recorded in [docs/MARKET-AND-OPTIONS.md](docs/MARKET-AND-OPTIONS.md), [docs/PLATFORM-BASELINE.md](docs/PLATFORM-BASELINE.md) and [docs/DISTRIBUTION-PLAN.md](docs/DISTRIBUTION-PLAN.md).

| Adjacent product | Publicly documented overlap | Product decision |
|---|---|---|
| WooCommerce core Orders | Order list/detail, statuses, notes, payment/stock state | Use core APIs; don't replace the order editor or treat all Pending/On hold as failures |
| Order Workflows | Custom statuses, condition-based transitions, notifications, transition log and export | Keep exception cases separate from order statuses |
| Stale Order Alerts | Age-based checks, admin/email alerts, manual recheck | Connect alerts to actionable cases, owners and outcomes |
| Failed Order Rescue | Failed/pending queue, manual payment-link follow-up and recovery tracking | Keep recovery as one possible exception path; no default customer email in MVP |
| Order Priority | Rule-based priority, badges, filters, bulk priority updates and audit notes | Make priority explainable within the exception queue |
| Assign Orders | Manual/rule-based assignment, emails and assigned-order access controls; Marketplace lists $79/year | Add case ownership without changing existing order permissions |
| Universal Workflow Automation | Broad triggers, conditions, actions, retry/history/dry-run and templates | Avoid a general workflow builder; restrict Hub rules to exception triage and low-risk internal actions |
| Out The Door | Order-linked tasks, priorities, due dates, Kanban/calendar and statuses | Cases must represent detected order exceptions with reason/evidence, not generic tasks |

**Product gap:** Exception resolution in one queue: order + reason + evidence + age + next step + owner/SLA + resolution outcome. Adjacent products cover pieces of this workflow, so the product must win on clarity and connected resolution tracking, not the number of automations.

Research is based on published listings and documentation, not hands-on plugin testing. The proposed $99/year remains unvalidated with customers.

## Phase 2: product and UX specification

**Status: complete (100%, 2026-11-06).** The React admin information architecture, inbox and case detail behavior, Free/Pro boundaries, workflow/assignment/SLA interactions, accessibility/localization requirements and UI states are specified in [docs/UX-SPECIFICATION.md](docs/UX-SPECIFICATION.md). React-based WordPress admin screens are implemented in Phases 5–6; runtime and accessibility verification remains open.

## Phase 3: architecture and data flow

**Status: complete (100%, 2026-11-06).** The case/event storage decision, WooCommerce detection and data flow, REST permission model, retention/privacy behavior, failure handling and Composer PSR-4 mapping are documented in [docs/ARCHITECTURE-DATA-FLOW.md](docs/ARCHITECTURE-DATA-FLOW.md) and [docs/ARCHITECTURE-DECISIONS.md](docs/ARCHITECTURE-DECISIONS.md). Phases 5–6 implement the Free and Pro source layers; the compatibility and release matrix remains unverified.

## Phase 4: pre-development package

**Status: complete (100%, 2026-11-06).** Implementation contracts, Free MVP acceptance, threat review, dated PHP/WP/WooCommerce/HPOS/database/WPML/Polylang test matrix, and release gates are recorded in [docs/PRE-DEVELOPMENT-PLAN.md](docs/PRE-DEVELOPMENT-PLAN.md). The plan was completed before implementation began; actual implementation evidence is recorded in the Phase 5 and Phase 6 reports.

## Phase 6: Pro capability implementation

**Status: complete (100%, 2026-11-06).** Added gated Pro source for opt-in administrator email alerts with deduplication and bounded retries; staff assignment without changing order permissions; calendar/business-hour SLA clocks, holidays, pause states, first-response marking, warnings and breach history; and versioned, bounded workflow rules with dry-run defaults, age/reason/state conditions, internal actions and run history. Pro queue filters, SLA visibility, assignment controls and settings are in the React admin UI. Payment capture, refunds, cancellation and payment-related order-status changes remain disabled; the UI and settings preserve that approval boundary. Details: [docs/PHASE-6-PRO-IMPLEMENTATION.md](docs/PHASE-6-PRO-IMPLEMENTATION.md).

**Packaging gate:** Pro source is activated only in a Pro build that defines `WPERH_PRO_EDITION`; licensing, paid package creation, compatibility checks and Marketplace review remain later release work. The planned $99/year price is still a hypothesis.

## Unified options bank

Each capability has one canonical key. Filter controls, rule logic and delivery behavior belong inside the owning capability; they are not duplicate settings.

| Key | Capability | Free | Pro | Boundary/notes |
|---|---|---|---|---|
| `exception.inbox` | Paginated central exception queue, sort, reason/state filters and next action | Yes | Yes | Primary workspace; order remains in WooCommerce |
| `exception.detection` | Detect pending/failed payment, missing shipping data, stock discrepancy and processing delay | Core rules | Configurable thresholds/rules | Don't misclassify legitimate offline/delayed payment |
| `exception.reason` | Reason taxonomy and explainable evidence | Core reasons | Configurable tags | One case can carry more than one reason |
| `exception.age_filters` | Staff-selected age buckets/date range | Yes | Yes | Separate from case-creation thresholds |
| `exception.search` | Search by WooCommerce order ID | Yes | Yes | Customer-field search is deferred pending privacy and permission review |
| `exception.saved_views` | Save reusable queue filters | — | Yes | Not a duplicate settings profile |
| `case.state` | Open, triaged, assigned, waiting, escalated, closed | Yes | Yes | Case lifecycle only; does not alter Woo order status |
| `case.notes` | Private resolution notes | Yes | Templates | Internal only; never send as customer note/email |
| `case.history` | Attributed case/rule/assignment/SLA/resolution event history | Basic | Pro adds workflow, assignment, SLA and delivery events | CSV export remains a later privacy/release decision |
| `resolution.outcome` | Resolved, dismissed, duplicate, waiting, escalated + reason | Yes | Yes | Terminal/decision reason distinct from current case state |
| `triage.priority` | Explainable priority from age/reason/SLA | Basic | Configurable rules | No standalone label-only workflow |
| `queue.bulk_tools` | Safe case-only bulk assignment/priority/outcome updates | — | Candidate | Never bulk-capture, refund or cancel orders |
| `alerts.channels` | Opt-in administrator email alerts, test delivery, bounded retries and event deduplication | — | Configurable recipients | No in-app alert center, customer email, or external service in this phase |
| `team.assignment` | Assign/reassign case to a staff member | — | Yes | No implicit order access/permission changes |
| `sla.policy` | Due times, timezone/business-hours choice, pause/breach tracking | — | Yes | Exact calendar/business-hour behavior specified in Phase 2 |
| `workflow.rules` | Bounded exception triage/assignment/admin-notification rules | — | Configurable | AND conditions (reason/state/age); dry-run default, pause by disable, versioned run events, max 3 attempts; no payment/order actions |
| `action.approval` | Approval boundary for future high-impact actions | Guardrail | Required policy; action execution disabled | No capture/refund/cancel/status-action endpoint is shipped; any future execution needs separate review |
| `action.execution` | Capture/refund/cancel/payment-implying status changes | Not in MVP | Future gated capability | Disabled by default; no silent execution; marketplace/gateway safety review first |
| `reporting.metrics` | Open case count and queue totals | Basic | Trends are a later phase | Export and aggregate reporting require separate privacy/performance review |
| `data.retention` | Retain/purge Hub-owned case data | Safe defaults | Configurable | Never delete WooCommerce order records |
| `privacy.data_rights` | Export/erase Hub-owned personal data where applicable | Yes | Yes | Separate from order deletion or WooCommerce record ownership |
| `integrations.multilingual` | Translation-ready admin UI and order-language context | Yes | Yes | WPML/Polylang are optional and must be tested |

### Systems bank (separate from settings)

1. **Detection:** WooCommerce order events plus bounded age checks; explain reason/evidence and avoid false positives.
2. **Idempotency:** One case row per order merges current reason codes; detection and workflow event keys suppress duplicate history/alerts for the same episode and retry.
3. **Case lifecycle:** State transitions with actor, source and timestamps; keep case state separate from order status.
4. **Queue/query:** WooCommerce order CRUD/query APIs, pagination and bounded queries; no direct order-table reads.
5. **Priority:** Deterministic, explainable scoring from reason, age and SLA.
6. **Assignment:** Owner and reassignment history without expanding user capabilities.
7. **SLA clock:** Timezone, business/calendar hours, pause and breach behavior.
8. **Notifications:** Admin channels, deduplication, throttling and delivery result.
9. **Rule engine:** Versioned bounded rules, dry-run, pause, retry limits and visible run history.
10. **Approval/action:** Proposal and execution separated; require fresh state, nonce/capability checks, human approval, idempotency and audit record.
11. **Storage/migration:** Hub case records separate from WooCommerce truth; schema upgrades, retention and uninstall behavior.
12. **Privacy/access:** Least privilege, data minimization, redacted logs and export/erase handling.
13. **Localization/compatibility:** WordPress i18n, HPOS plus supported legacy storage, WPML and Polylang verification.
14. **Performance/observability:** Measured query cost, cache invalidation and actionable errors without customer/payment payloads.

No CLI tasks or CLI work items are included before the plugin is final.

## Technical direction and constraints

- **Admin UI:** React-based WordPress admin application, using WordPress-provided packages (`@wordpress/element`, `@wordpress/components` and related packages) and WordPress/WooCommerce design conventions. Ship compiled assets; do not load React from a public CDN. The React/WordPress design direction is specified in Phases 2–4; exact package versions and build integration are verified during implementation.
- **PHP structure:** Use Composer PSR-4 mapping `WPEssential\OrderRescueHub\` to `includes/`. The source ships Composer's ClassLoader runtime, PSR-4 map to `includes/`, and directly loaded `vendor/autoload.php`; the final production artifact must be regenerated/reconciled with Composer metadata after the no-CLI development window closes. This requirement is architecture in Phase 3 and implementation acceptance in Phase 5. No CLI task is added before plugin finalization.
- **Order access:** Use WooCommerce order CRUD/query APIs (`WC_Order`, `wc_get_order`, `wc_get_orders` / `WC_Order_Query`) and declare HPOS compatibility only after tests. Do not directly query or mutate `posts`, `postmeta`, or WooCommerce order tables for order data.
- **Integration boundary:** The add-on should detect WooCommerce availability, fail gracefully when dependencies are missing, and keep the WPEssential suite relationship optional unless a concrete shared-core dependency is justified.
- **CLI deferral:** Do not create, schedule, implement, or run any CLI-related task before the plugin is final. Do not add WP-CLI commands or CLI-only merchant setup. Revisit CLI work only after finalization.
- **Supported versions:** Exact minimum PHP, WordPress, WooCommerce, browser, and database versions remain **TBD** until market, hosting, and test-matrix review. Phase 1 must record a proposed baseline and the evidence for it. Do not advertise compatibility until verified.
- **Multilingual:** English source strings, translation-ready text domain, plural/context support. Validate WPML integration (including `wpml-config.xml` only if plugin-owned translatable settings require it) and Polylang behavior. No claim of official certification without the vendor process.
- **Licensing and distribution:** Target WordPress.org for the Free edition and WooCommerce.com Marketplace for paid Pro. The exact Pro listing model, billing, vendor account, GPL-compatible license, trademark checks, package split and submission evidence are tracked in `docs/DISTRIBUTION-PLAN.md`; do not imply approval before both review processes finish.

## AI collaboration instructions

Read this file and `AGENTS.md` before changing the project. Treat `docs/` as the source of truth for product scope, option keys, architecture decisions and acceptance criteria. If instructions conflict, follow `AGENTS.md`, then this README, then the relevant technical specification; record unresolved conflicts instead of silently changing scope.

AI tools must:

1. Start by stating the current phase and the exact deliverable being changed.
2. Inspect existing files and preserve decisions; do not invent completed work or report unrun verification as passing.
3. Keep the option bank canonical: propose a new capability key only after checking for an equivalent existing key.
4. Do not add, schedule, implement, or run a CLI-related task before the plugin is final. Payment/refund/cancel execution, external telemetry, dependencies, licensing services, and customer-facing communication remain separately gated.
5. Keep implementation within the approved phase; for UI work, use React and WordPress admin conventions, and do not alter unrelated product behavior.
6. Update the phase table only when evidence supports a status/progress change. Add a dated entry to `docs/STATUS.md` for every meaningful update, including completed work, blockers, and verification performed.
7. Include security, HPOS, multilingual, accessibility, performance, and failure-mode implications in relevant decisions.
8. Do not commit secrets, customer data, production exports, or credentials. Never use live payment/customer data as a test fixture.
9. Prefer small, reviewable changes with acceptance criteria. Do not claim 100% compatibility or a perfect replica without reproducible evidence.

## Source notes

Phase 1 market and platform review completed 2026-11-06 using official WooCommerce/WordPress documentation and marketplace/directory listings. The scan is documented in `docs/MARKET-AND-OPTIONS.md` and `docs/PLATFORM-BASELINE.md`; it is not hands-on product testing or customer willingness-to-pay research.

- WooCommerce order troubleshooting: https://woocommerce.com/document/managing-orders/troubleshooting-orders/
- WooCommerce order statuses: https://woocommerce.com/document/managing-orders/order-statuses/
- WooCommerce Order Workflows: https://woocommerce.com/document/order-workflows/
- WooCommerce HPOS recipe book: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/
- WordPress plugin handbook: https://developer.wordpress.org/plugins/
- WordPress React element package: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/
- WPML WooCommerce translation: https://wpml.org/documentation/translating-your-contents/translate-woocommerce/
- Polylang for WooCommerce: https://polylang.pro/documentation/support/guides/polylang-wc-installation/
- Market comparison: `docs/MARKET-AND-OPTIONS.md`.
- Platform requirements: `docs/PLATFORM-BASELINE.md`.
- PHP/WordPress/WooCommerce/WPML/Polylang and Marketplace source links are listed in those documents.

## Distribution strategy

The product is planned for two separate submission tracks: **Free edition to the WordPress.org Plugin Directory** and **paid Pro edition to the WooCommerce.com Marketplace**. This is a release target, not an assertion of approval. Both listings depend on independent review and any reviewer feedback. Keep one canonical codebase/option bank, with clearly separated packages and no duplicated feature settings.

| Track | Package and submission plan | Required release gates |
|---|---|---|
| WordPress.org — Free | Complete, installable production ZIP with the Free feature set, directory readme/assets and support details; submit through the official plugin submission process; after approval publish the approved release through the assigned SVN repository | GPL-compatible license (GPL v2 or later); detailed guideline, security, trademark and privacy review; no placeholders/dev artifacts; test on the supported version matrix; document any external service; prepare support and update ownership |
| WooCommerce.com — Pro (paid) | Submit a production-ready Pro extension/package for a paid Marketplace listing; confirm whether it should be paid standalone or Marketplace freemium | Approved vendor/partner application; business/revenue-share review; trademark-compliant name; latest two WP/Woo major releases; accessibility, security/UX/docs and QIT evidence; pricing parity. **PHP policy conflict:** Marketplace guidance still lists 7.4+ while WooCommerce 11.6 will require 8.1+ and PHP 8.1 is EOL; project targets PHP 8.3+, pending written vendor clarification before submission (details: `docs/PLATFORM-BASELINE.md`) |

The Pro price of **$99/year remains a proposal**. Confirm its billing/licensing fit with Woo Marketplace before finalizing it. Woo Marketplace may require its own license/billing and revenue-share terms; document the selected listing and support model before implementation is packaged. The free WordPress.org build must provide substantive standalone value and follow the full directory rules; upgrade messaging must stay compliant and non-intrusive.

**Submission is not guaranteed.** Do not mark either route complete until the package has passed internal checks, been submitted, reviewer feedback has been addressed, and approval/listing is verifiably granted. Submission and reviewer turnaround dates are estimates; external review can extend the schedule. No CLI interface will be shipped as a plugin feature. Internal release/test workflows must be documented separately and must not become a merchant requirement.

Official references checked 2026-11-06:

- WordPress.org directory overview and license requirements: https://developer.wordpress.org/plugins/wordpress-org/
- WordPress.org submission and SVN publishing flow: https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/
- WordPress.org detailed plugin guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- Woo Marketplace product submission checklist: https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product
- Woo Marketplace getting started and vendor review flow: https://developer.woocommerce.com/docs/woo-marketplace/getting-started/
- Woo Marketplace monetization expectations, including paid and freemium options: https://developer.woocommerce.com/docs/woo-marketplace/monetization-expectations


## Phase 7: Compatibility and quality

**Status: in progress (35%, started 2026-11-07).** Static source audit fixed the Pro bootstrap reference, schema-version gate, manual transition/SLA hook, admin timezone/holiday parsing, and rotating bounded Pro scan pages. JavaScript syntax parsing passed. PHP runtime/lint, WordPress/WooCommerce, HPOS, WPML/Polylang, security, accessibility, performance, and package gates are still pending; see [docs/PHASE-7-QUALITY-REPORT.md](docs/PHASE-7-QUALITY-REPORT.md). Compatibility is not certified yet.
