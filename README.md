# WPEssential Order Rescue Hub for WooCommerce

**Repository slug:** `wpessential-order-rescue-hub-woocommerce`  
**Product type:** WPEssential add-on for WooCommerce (planned)  
**Development status:** Pre-development planning  
**Current release:** None

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
| 1. Market and requirements | Competitor map, option bank, gap statement, version matrix | In progress | `██░░░ 35%` | 2026-11-06 | 2026-11-10 |
| 2. Product and UX specification | Jobs-to-be-done, information architecture, React admin UI flows, accessibility requirements | Planned | `░░░░░ 0%` | 2026-11-11 | 2026-11-14 |
| 3. Architecture and data flow | Storage model, event lifecycle, permissions, retention, failure handling | Planned | `░░░░░ 0%` | 2026-11-15 | 2026-11-19 |
| 4. Pre-development package | Technical decisions, acceptance criteria, threat model, release and test matrix | Planned | `░░░░░ 0%` | 2026-11-20 | 2026-11-23 |
| 5. Free MVP implementation | Exception inbox, filters, notes, audit history, accessible admin UI | Not started | `░░░░░ 0%` | 2026-11-24 | 2026-12-08 |
| 6. Pro capabilities | Alerts, workflow rules, assignment, SLA controls, approvals | Not started | `░░░░░ 0%` | 2026-12-09 | 2026-12-22 |
| 7. Compatibility and quality | WordPress/WooCommerce matrix, HPOS, WPML, Polylang, security, performance | Not started | `░░░░░ 0%` | 2026-12-23 | 2027-01-08 |
| 8. WordPress.org Free submission | GPL-compatible Free package, readme/assets, directory review submission and SVN release preparation | Not started | `░░░░░ 0%` | 2027-01-09 | 2027-01-22 |
| 9. WooCommerce.com Pro submission | Vendor application, paid Pro package, QIT/UX evidence, Marketplace review and feedback fixes | Not started | `░░░░░ 0%` | 2027-01-23 | 2027-02-05 |
| 10. Release and maintenance readiness | Approval-dependent launch checklist, versioned releases, support and update process | Not started | `░░░░░ 0%` | 2027-02-06 | 2027-02-12 |

**Overall:** Planning only; no plugin code or release build exists. Dates are provisional, not delivery commitments.

## Phase 1: market scan and product gap

The initial scan shows adjacent products already cover custom order statuses and transitions, trigger/action workflows, stale-order notifications, and failed-payment follow-up. WooCommerce core also exposes order status, order notes, hold-stock behavior, and order troubleshooting information.

| Adjacent solution | Existing strength | Product implication |
|---|---|---|
| WooCommerce core order management and troubleshooting | Order lists, statuses, order details/notes, stock hold behavior, troubleshooting guidance | Integrate with core order APIs; do not duplicate the order editor or payment gateway troubleshooting UI |
| WooCommerce Order Workflows | Custom statuses, transition rules, notifications, automation and transition log | Do not compete as another status/workflow engine; focus on exception detection, ownership, SLA and resolution evidence |
| Stale Order Alerts for WooCommerce | Age/SLA based alerts for long-running statuses | Alerts alone are not differentiation; connect alerts to a queue, assignment and resolution lifecycle |
| Failed Order Rescue for WooCommerce | Follow-up for failed, pending or cancelled checkout orders | Keep recovery messaging as one optional exception type; support broader operational exceptions |
| Order Priority for WooCommerce | Rule-based priority labels and filters | Priority can be an input to triage, but the hub must represent reason, age, owner, next action and outcome |
| Laqi Order Status Workflows / general automation plugins | Rules, status changes, email/webhook actions and activity records | Avoid broad condition/action sprawl; workflows must be bounded, explainable and approval-aware |

**Provisional differentiation:** One operational exception case per order/reason, with age, evidence, next step, owner, SLA and resolution outcome visible together. Competitor overlap must be rechecked before feature lock and pricing.

## Unified options bank

Each capability has one canonical key to prevent duplicates across Free and Pro. Edition differences are permissions/limits on the same capability, not duplicate settings.

| Key | Capability | Free | Pro | Notes |
|---|---|---|---|---|
| `exception.inbox` | Central queue with order, detected reason, age, status and next action | Yes | Yes | Primary screen |
| `exception.reason` | Payment, shipping data, stock, processing delay, and configurable reason taxonomy | Core reasons | Configurable rules | A case may have multiple reason tags |
| `exception.age_filters` | Age buckets and date range | Yes | Yes | Stable deterministic filters |
| `exception.search` | Search by order number/customer where permitted | Yes | Yes | Respect capabilities and privacy |
| `case.notes` | Internal resolution notes | Manual | Manual + templates | Never expose internal notes to customers |
| `case.history` | Case creation, assignment, rule and resolution audit events | Basic | Extended/export | No duplicate “activity log” setting |
| `triage.priority` | Priority score/label | Basic age-derived | Configurable criteria | Explain why priority was assigned |
| `alerts.channels` | Admin in-app/email alerts | — | Configurable | Rate limits and deduplication |
| `workflow.rules` | If/then exception triage and notification rules | — | Configurable | Dry run, pause, version and event history |
| `team.assignment` | Assign/reassign case to a user | — | Yes | Capability-aware; unassigned queue remains available |
| `sla.policy` | Due time, breach state, pause/resume and breach reporting | — | Yes | Store timezone-aware timestamps; clarify calendar/business hours |
| `resolution.outcome` | Resolved, dismissed, duplicate, waiting, escalated + reason | Yes | Yes | Consistent outcome model |
| `action.approval` | Approval gate for risky order actions | Guardrail | Configurable policies | Required for any future action execution |
| `action.execution` | Execute capture/refund/cancel/status operations | No | Disabled by default | Only if safe APIs and gateway semantics permit; otherwise link to native order action |
| `reporting.metrics` | Open cases, time-to-resolution, SLA breaches, reason trends | Basic counts | Trends/export | Aggregate without unnecessary customer data |
| `data.retention` | Retention and purge controls for plugin-owned data | Defaults | Configurable | Never delete WooCommerce order records |
| `integrations.multilingual` | Translatable plugin strings and locale-aware UI | Yes | Yes | WPML/Polylang compatibility is a test target, not an assumed certification |

### Systems bank (distinct from settings)

1. **Detection:** Map WooCommerce order states/events to explicit exception rules; avoid direct table reads and avoid labeling a normal delayed/offline payment as failure without evidence.
2. **Case lifecycle:** Open → triaged → assigned/waiting/escalated → resolved/dismissed. Persist transitions and actor/source.
3. **Queue/query:** Paginate and filter through WooCommerce-supported order query APIs; index plugin-owned case metadata and measure query cost on large stores.
4. **Rules engine:** Deterministic, versioned rules; preview/dry-run; bounded action set; retries with backoff; deduplicate repeated events.
5. **Notifications:** Channel preferences, throttling, quiet hours if validated, and delivery outcome. Do not make external network calls by default.
6. **Approval and execution:** Separate proposal from execution, re-check order/payment state immediately before action, use idempotency and capability/nonce validation, record success/failure.
7. **Access control:** WordPress capabilities, least privilege, nonces, sanitization/escaping, audit attribution, and customer-data minimization.
8. **Persistence and retention:** Store case/assignment/SLA data separately from WooCommerce order data; define schema versioning, uninstall behavior, retention and export/delete obligations.
9. **Multilingual:** Use WordPress translation APIs and stable text domain; test admin UI and notices with WPML and Polylang without coupling core behavior to either product.
10. **Observability:** Admin-visible rule run result and actionable error; no customer/order payloads in routine logs; redact secrets and payment data.

## Technical direction and constraints

- **Admin UI:** React-based WordPress admin application, using WordPress-provided packages (`@wordpress/element` and related packages) and WordPress design conventions. Ship compiled assets; do not load React from a public CDN. Final component and build approach is an architecture decision in Phase 3.
- **Order access:** Use WooCommerce order CRUD/query APIs (`WC_Order`, `wc_get_order`, `wc_get_orders` / `WC_Order_Query`) and declare HPOS compatibility only after tests. Do not directly query or mutate `posts`, `postmeta`, or WooCommerce order tables for order data.
- **Integration boundary:** The add-on should detect WooCommerce availability, fail gracefully when dependencies are missing, and keep the WPEssential suite relationship optional unless a concrete shared-core dependency is justified.
- **No product CLI:** No WP-CLI commands, shell interface, or CLI-only setup will be added before the plugin is final. Build tooling used privately by developers is a separate implementation detail and must not become a user-facing requirement without an explicit decision.
- **Supported versions:** Exact minimum PHP, WordPress, WooCommerce, browser, and database versions remain **TBD** until market, hosting, and test-matrix review. Phase 1 must record a proposed baseline and the evidence for it. Do not advertise compatibility until verified.
- **Multilingual:** English source strings, translation-ready text domain, plural/context support. Validate WPML integration (including `wpml-config.xml` only if plugin-owned translatable settings require it) and Polylang behavior. No claim of official certification without the vendor process.
- **Licensing and distribution:** Target WordPress.org for the Free edition and WooCommerce.com Marketplace for paid Pro. The exact Pro listing model, billing, vendor account, GPL-compatible license, trademark checks, package split and submission evidence are tracked in `docs/DISTRIBUTION-PLAN.md`; do not imply approval before both review processes finish.

## AI collaboration instructions

Read this file and `AGENTS.md` before changing the project. Treat `docs/` as the source of truth for product scope, option keys, architecture decisions and acceptance criteria. If instructions conflict, follow `AGENTS.md`, then this README, then the relevant technical specification; record unresolved conflicts instead of silently changing scope.

AI tools must:

1. Start by stating the current phase and the exact deliverable being changed.
2. Inspect existing files and preserve decisions; do not invent completed work or report unrun verification as passing.
3. Keep the option bank canonical: propose a new capability key only after checking for an equivalent existing key.
4. Ask before adding CLI commands, payment/refund/cancel execution, external telemetry, dependencies, licensing services, or customer-facing communication features.
5. Keep implementation within the approved phase; for UI work, use React and WordPress admin conventions, and do not alter unrelated product behavior.
6. Update the phase table only when evidence supports a status/progress change. Add a dated entry to `docs/STATUS.md` for every meaningful update, including completed work, blockers, and verification performed.
7. Include security, HPOS, multilingual, accessibility, performance, and failure-mode implications in relevant decisions.
8. Do not commit secrets, customer data, production exports, or credentials. Never use live payment/customer data as a test fixture.
9. Prefer small, reviewable changes with acceptance criteria. Do not claim 100% compatibility or a perfect replica without reproducible evidence.

## Source notes

Initial desk research checked WooCommerce official order management/status/workflow and HPOS documentation, WordPress plugin/React documentation, WordPress.org product pages for adjacent plugins, and WPML/Polylang documentation. This is a directional scan, not an exhaustive market or commercial validation. Revisit before Phase 1 is marked complete.

- WooCommerce order troubleshooting: https://woocommerce.com/document/managing-orders/troubleshooting-orders/
- WooCommerce order statuses: https://woocommerce.com/document/managing-orders/order-statuses/
- WooCommerce Order Workflows: https://woocommerce.com/document/order-workflows/
- WooCommerce HPOS recipe book: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/
- WordPress plugin handbook: https://developer.wordpress.org/plugins/
- WordPress React element package: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/
- WPML WooCommerce translation: https://wpml.org/documentation/translating-your-contents/translate-woocommerce/
- Polylang for WooCommerce: https://polylang.pro/documentation/support/guides/polylang-wc-installation/
- Adjacent products: https://wordpress.org/plugins/stale-order-alerts-for-woocommerce/ ; https://wordpress.org/plugins/failed-order-rescue-for-woocommerce/ ; https://woocommerce.com/products/order-priority/

## Distribution strategy

The product is planned for two separate submission tracks: **Free edition to the WordPress.org Plugin Directory** and **paid Pro edition to the WooCommerce.com Marketplace**. This is a release target, not an assertion of approval. Both listings depend on independent review and any reviewer feedback. Keep one canonical codebase/option bank, with clearly separated packages and no duplicated feature settings.

| Track | Package and submission plan | Required release gates |
|---|---|---|
| WordPress.org — Free | Complete, installable production ZIP with the Free feature set, directory readme/assets and support details; submit through the official plugin submission process; after approval publish the approved release through the assigned SVN repository | GPL-compatible license (GPL v2 or later); detailed guideline, security, trademark and privacy review; no placeholders/dev artifacts; test on the supported version matrix; document any external service; prepare support and update ownership |
| WooCommerce.com — Pro (paid) | Submit a production-ready Pro extension/package for a paid Marketplace listing; confirm with Marketplace review whether it should be listed as a paid standalone extension depending on the WordPress.org Free base, or through the Marketplace freemium model | Approved vendor/partner application; product/business and revenue-share review; trademark-compliant name; supported two latest major WordPress and WooCommerce versions; PHP 7.4 minimum stated by current submission guidance (target a newer secure baseline, with PHP 8.3+ strongly recommended); WordPress accessibility standards; security/UX/documentation review; QIT evidence for API, E2E, activation, security, PHP compatibility, malware and validation; pricing parity across sales channels |

The Pro price of **$99/year remains a proposal**. Confirm its billing/licensing fit with Woo Marketplace before finalizing it. Woo Marketplace may require its own license/billing and revenue-share terms; document the selected listing and support model before implementation is packaged. The free WordPress.org build must provide substantive standalone value and follow the full directory rules; upgrade messaging must stay compliant and non-intrusive.

**Submission is not guaranteed.** Do not mark either route complete until the package has passed internal checks, been submitted, reviewer feedback has been addressed, and approval/listing is verifiably granted. Submission and reviewer turnaround dates are estimates; external review can extend the schedule. No CLI interface will be shipped as a plugin feature. Internal release/test workflows must be documented separately and must not become a merchant requirement.

Official references checked 2026-11-06:

- WordPress.org directory overview and license requirements: https://developer.wordpress.org/plugins/wordpress-org/
- WordPress.org submission and SVN publishing flow: https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/
- WordPress.org detailed plugin guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- Woo Marketplace product submission checklist: https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product
- Woo Marketplace getting started and vendor review flow: https://developer.woocommerce.com/docs/woo-marketplace/getting-started/
- Woo Marketplace monetization expectations, including paid and freemium options: https://developer.woocommerce.com/docs/woo-marketplace/monetization-expectations
