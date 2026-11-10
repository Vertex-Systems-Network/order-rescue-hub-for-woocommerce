# Pre-development plan

**Status:** Phase 4 pre-development package completed before implementation (2026-11-06).  
**Scope:** This document records implementation contracts, acceptance criteria, threat review and compatibility/release test plan as they stood at the Phase 4 handoff. Source work completed later is tracked in the Phase 5/6 reports; the compatibility/release tests are still outstanding.

## 1. Frozen product constraints

- Product: Order Rescue Hub as a WPEssential WooCommerce add-on; Free edition has standalone value, paid Pro remains separately gated.
- Canonical feature/settings bank: README. Do not create duplicate keys or implement capabilities outside the active edition.
- WooCommerce remains the order source of truth. Case lifecycle stays separate from order status.
- Order reads/queries use WooCommerce CRUD/query APIs. Never address WooCommerce order tables directly.
- Free MVP: exception inbox, reason/evidence, age filtering, search, private notes, case history, safe case outcomes.
- Pro after Free: admin alerts, bounded workflows, assignment, SLA, expanded reporting.
- No customer communication or financially consequential order actions in MVP. Any future order action requires a separate approval gate.
- React admin uses WordPress/WooCommerce conventions and bundled, local runtime assets.
- PHP classes use Composer PSR-4 mapping WPEssential\OrderRescueHub\ to includes/. Bootstrap directly loads vendor/autoload.php. Composer-generated autoloader is the sole class loader; no custom SPL loader or per-class include list.
- No CLI task/backlog item, merchant-run command, or CLI-only setup is part of this plan before final plugin readiness.

## 2. Implementation contracts

### PHP and plugin layout

Planned layout (final names may change only through a documented architecture decision):

| Path | Responsibility |
|---|---|
| Main plugin bootstrap | Plugin metadata, direct-access guard, dependency gate, direct vendor/autoload.php load, service boot |
| includes/ | PSR-4 namespaced domain, application, adapter, REST and admin PHP classes |
| vendor/ | Composer-generated production autoloader and required metadata |
| assets/ | Built React/CSS assets and WordPress-compatible asset dependency metadata |
| languages/ | Translation template and generated locale files |
| docs/ | Product, architecture, release and contributor instructions |

The Composer manifest maps WPEssential\OrderRescueHub\ to includes/. The production archive must include the generated Composer loader and only the runtime files it needs. The merchant must not install Composer. Do not substitute a handwritten SPL registration routine.

### Runtime and dependency behavior

- Plugin bootstrap checks WordPress/PHP minimums and WooCommerce availability before registering WooCommerce-dependent services.
- If WooCommerce is inactive or too old, plugin admin shows a clear dependency notice; public requests and activation do not fatal.
- HPOS compatibility is declared only after the Phase 7 matrix passes on HPOS and legacy order storage.
- Plugin settings/options are schema-versioned and validated. Case tables are created/upgraded using WordPress schema conventions; upgrades are idempotent and preserve records.
- All scheduled work is bounded, locked, retry-limited, and observable. Actual scheduler delays are shown; SLA is not represented as real-time.
- React reads/writes only through explicit versioned REST routes; server-side validation and authorization are authoritative.
- No service telemetry, remote licensing, or customer message integrations are introduced without a separate documented decision.

## 3. Ordered Free MVP work packages

These are code/product work packages, not CLI tasks. They define implementation order and dependencies; they do not authorize jumping into implementation during Phase 4.

| ID | Work package | Depends on | Acceptance evidence |
|---|---|---|---|
| PRE-01 | Plugin bootstrap, dependency gate and Composer PSR-4 runtime loading | None | Plugin loads through Composer-generated vendor/autoload.php; namespace maps WPEssential\OrderRescueHub\ to includes/; no merchant-side Composer; missing WooCommerce fails gracefully |
| PRE-02 | Schema installer/upgrader and repositories | PRE-01 | Case/event tables are versioned, site-prefixed, indexed, idempotently upgraded, and do not access order tables |
| PRE-03 | WooCommerce adapter and reason classifier | PRE-02 | Reads orders only through CRUD/query APIs; filters legitimate pending/offline, virtual, excluded and stale cases correctly; evidence is allow-listed |
| PRE-04 | Detection worker, idempotency and age sweep | PRE-03 | Duplicate/retried signals do not duplicate cases/events; bounded sweeps do not overlap; deleted orders and failures are safely handled |
| PRE-05 | Case lifecycle and event service | PRE-02 | Valid transitions, episode reopen, optimistic version checks and private note audit are enforced server-side |
| PRE-06 | REST endpoints and permission policy | PRE-05 | Per-route and per-order authorization, nonce/CSRF checks, validation, bounded pagination, safe errors and redacted logs |
| PRE-07 | React exception inbox and case detail | PRE-06 | UX specification is met; keyboard operation, status announcements, empty/loading/error/stale/permission states, RTL and narrow admin layout work |
| PRE-08 | Settings, retention and privacy integrations | PRE-02 | Defaults, export/erase, retention and uninstall choice are documented and bounded; purge never deletes WooCommerce orders |
| PRE-09 | Free package boundary and documentation | PRE-01 through PRE-08 | Installable Free package, clear setup/dependency notices, no Pro-only controls exposed as functional, WordPress.org-ready readme/license/credits |

Pro work packages are not part of Free MVP completion. They require separate workflow safety review, license/package decisions, and acceptance evidence before implementation.

## 4. Definition of Done for implementation changes

Each implementation ticket or pull request must:

1. Link to the canonical option key and this specification’s acceptance criteria.
2. Include the user-visible behavior and permission model.
3. Use WooCommerce order CRUD/query APIs; demonstrate no direct order-table access in the changed code.
4. Validate capability, per-order access, nonce, input schema and output escaping on server-side boundaries.
5. Add/adjust tests and run the relevant documented suite once implementation begins; report exact evidence and failures. Do not claim unrun tests.
6. Cover HPOS/legacy implications, translations, RTL, accessibility, retention and performance where applicable.
7. Avoid customer data in logs/fixtures; redact errors and preserve audit attribution.
8. Include a reversible migration or explain why no migration is needed.
9. Update README phase progress and docs/STATUS.md only when acceptance evidence justifies it.
10. Include license/source notices for shipped Composer-generated files and dependencies.
11. Not add a CLI task or user-facing command before final plugin readiness.

## 5. Threat review

| Threat | Risk | Required control and review evidence |
|---|---|---|
| Unauthorized order/case access | Staff sees customer/order data beyond their role | Capability check on every route plus current per-order permission check; test allowed/denied role matrix |
| CSRF or forged mutation | Unauthorized case transition or note | REST nonce for cookie-authenticated admin requests plus authorization, schema validation and state graph checks |
| IDOR on case/order IDs | Guessable ID exposes another case/order | Check case permission and underlying order permission before returning data or mutating |
| Stored/reflected XSS in notes/evidence | Admin script execution from user-controlled data | Sanitize on input where appropriate, escape on output, React text rendering, HTML not accepted in notes |
| SQL injection / unbounded query | Data exposure or site load | Prepared DB operations, allow-listed sort/filter fields, bounded pagination and query caps |
| Duplicate or replayed background event | Duplicate case, alerts or inconsistent closure | Unique event idempotency key, row version, notification dedupe and bounded retry |
| Sensitive data leakage | Payment/customer details in DB, logs, exports | Data allow-list, no full snapshots/gateway payloads, redaction, retention and privacy exporter/eraser |
| Privilege escalation through assignment | Assignment grants order access | Assignment is routing metadata only; access checked independently using WooCommerce capabilities |
| Dangerous automation | Refund/capture/cancel occurs without informed approval | Excluded from MVP; if reconsidered, proposal/approval/execution separation, fresh state, idempotency and audit |
| Scheduler abuse / resource exhaustion | Overlapping scans or high-volume work | Candidate event queue, batch cap, short-lived lock, cursor, backoff, bounded retention batches |
| Dependency or Composer supply-chain issue | Vulnerable or incompatible vendor files | No runtime dependency unless justified; lock and audit dependencies, include licenses/notices, ship only production-required generated artifacts |
| Data destruction on uninstall | Merchant loses case history unexpectedly | Retain by default; explicit destructive opt-in, clear disclosure and Hub-only deletion |

## 6. Compatibility and verification matrix

This is the required pre-release matrix, not evidence that compatibility has been tested. Current versions below were checked 2026-11-06 and must be refreshed in Phase 7 and again before each submission.

| Dimension | Planned minimum / baseline | Test coverage required |
|---|---|---|
| PHP | Plugin minimum target 8.3; run a latest supported PHP branch as well | PHP 8.3, 8.4, and 8.5 on the supported WordPress/WooCommerce set |
| WordPress | Minimum target 7.0; stable-major branches 7.0 and 7.1 | Latest stable patch for each branch: 7.0.8 and 7.1.4 at snapshot date |
| WooCommerce | Minimum target 11.1; stable-major branches 11.1 and 11.2 | Latest stable patch for each: 11.1.2 and 11.2.2 at snapshot date |
| Order storage | HPOS required; legacy post storage where supported | Run core case flows in both modes; test compatibility declaration and migrations |
| Database | WordPress recommended baseline: MySQL 8.0+ or MariaDB 10.11+ | Validate custom schema and indexes on supported MySQL/MariaDB versions; never rely on unverified JSON-column/index features |
| React/admin | WordPress package versions available on target WP branches; Woo admin patterns | Keyboard, WCAG 2.2 AA, all admin color schemes, RTL, narrow viewport, reduced motion, loading/empty/error states |
| Distribution | Free WordPress.org and paid WooCommerce.com | WordPress.org Plugin Check/guideline/security review; Woo QIT and current Marketplace UX/accessibility/security gates; source/license review |

### Required test scenarios before compatibility claims

- Fresh install, missing WooCommerce, supported minimum dependency, activation/deactivation, upgrade from prior schema and rollback/recovery behavior.
- HPOS on/off, order CRUD, payment/status transitions, virtual vs shippable order, offline payment grace period, missing address, stock discrepancy, processing age threshold.
- Duplicate/replayed signals, lock conflict, high-volume pagination, scheduler delay, deleted order, write failure, notification failure, stale case version.
- Capability matrix for admin/order manager/support/read-only user; direct REST requests with missing nonce/capability; cross-order/case ID attempts.
- XSS/SQL validation for notes, settings, filters, exported data and error handling.
- Retention boundary, privacy export/erase, uninstall keep/purge choice, multisite per-site isolation.
- WordPress translation extraction, locale formatting, and RTL behavior; third-party multilingual integrations are out of scope.
- React UI keyboard-only and assistive-technology review; contrast/color scheme; mobile-width admin fallback; WCAG 2.2 AA manual review plus automated accessibility scan.
- Performance profiling against a representative large order dataset, query count and memory; set numeric budgets in Phase 7 based on baseline measurement.

## 7. Release gates and ownership

- Phase 7 owns the executed compatibility/security/performance matrix and numeric performance budgets.
- WordPress.org Free and WooCommerce.com paid Pro remain separate submissions and approval tracks.
- Minimum PHP and WordPress/WooCommerce header values must match the tested package and active distribution rules.
- Woo Marketplace’s generic minimum PHP 7.4 requirement conflicts with the project’s planned PHP 8.3 minimum. Do not lower the security baseline without written policy clarification; record reviewer/vendor outcome before submission.
- WordPress and WooCommerce recommend PHP 8.3 or newer; WordPress recommends MySQL 8.0 or MariaDB 10.11+. Project does not advertise tested support until verification evidence exists.
- Keep admin strings translation-ready and preserve locale-aware formatting and RTL behavior; third-party multilingual integrations are out of scope.
- Current WordPress, WooCommerce, PHP, and database targets are recorded in `docs/PLATFORM-BASELINE.md`.

## 8. Phase 4 acceptance checklist

- [x] Product/architecture constraints are consolidated for implementation handoff.
- [x] Ordered Free MVP work packages and dependencies are defined.
- [x] Definition of Done is documented.
- [x] Threats, controls and evidence requirements are documented.
- [x] PHP, WordPress, WooCommerce, database, and HPOS matrix is dated and source-backed; third-party multilingual integrations are excluded by owner decision.
- [x] Functional, security, privacy, accessibility, performance and distribution test scenarios are specified.
- [x] Composer PSR-4 uses includes/ and direct vendor/autoload.php load; no custom SPL loader.
- [x] No CLI task or CLI work item has been added.

**Evidence:** This plan plus updated platform baseline, Composer path corrections, README phase tracking, AI handoff backlog and status log.  
**Not performed in Phase 4:** No implementation, release build, Composer artifact, test execution, CLI task, or marketplace submission.

## 9. Official references

Snapshot checked 2026-11-06:

- WordPress releases: https://wordpress.org/download/releases/
- WordPress server requirements: https://wordpress.org/about/requirements/
- WordPress/PHP support clarification: https://make.wordpress.org/core/2026/05/22/php-support-clarification-2026/
- WooCommerce releases: https://developer.woocommerce.com/releases/
- WooCommerce server recommendations: https://woocommerce.com/document/update-php-wordpress/
- PHP supported versions: https://www.php.net/supported-versions.php
- WooCommerce 11.6 PHP requirement notice: https://developer.woocommerce.com/2026/09/29/php-8-1-requirement/
- Woo Marketplace submission requirements: https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product
- WordPress.org plugin guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
