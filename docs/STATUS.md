# Project status log

## 2026-11-06 — Initial product framing

- **Phase:** 1 — Market and requirements (in progress).
- **Completed:** Defined the exception-workspace positioning, Free/Pro hypothesis, payment-action safety boundary, initial unified option bank, distinct subsystem map, AI collaboration rules, and provisional phase schedule.
- **Research reviewed:** WooCommerce core order status/troubleshooting and order workflow documentation; WooCommerce HPOS guidance; WordPress plugin and React package documentation; adjacent order workflow, stale-order alert, order priority, and failed-payment recovery products; WPML and Polylang WooCommerce documentation.
- **Progress:** Phase 1 at 35% (initial scan only). Competitor inventory, overlap validation, version baseline, and final requirements remain open.
- **Verification:** Documentation review only. No code, automated tests, compatibility test, or security test has been run.
- **Open questions:** Supported PHP/WP/WooCommerce baseline; Pro delivery/licensing; precise SLA semantics; whether any order action execution belongs in product; scale targets and retention defaults.


## 2026-11-06 — Distribution tracks added

- **Phase:** 1 — Market and requirements (in progress).
- **Completed:** Added the explicit Free-to-WordPress.org and paid-Pro-to-WooCommerce.com submission goals; split the release roadmap into directory submission, Marketplace submission, and approval-dependent launch/maintenance phases; documented current official review gates in `docs/DISTRIBUTION-PLAN.md`.
- **Requirements recorded:** GPL-compatible Free edition; production ZIP/readme and WordPress.org guideline/security review; approved Woo Marketplace vendor application; Marketplace business/revenue-share, trademark, pricing, compatibility, accessibility, QIT and product-review gates.
- **Research reviewed:** Official WordPress.org Plugin Handbook submission/guideline docs and Woo Marketplace submission, getting-started and monetization docs (checked 2026-11-06).
- **Progress:** Phase 1 remains at 35%; this adds validated distribution requirements, while competitor completeness, exact supported-version matrix, licensing/Pro billing choice and pricing validation remain open.
- **Verification:** Confirmed GitHub README and status file content before editing; reviewed official submission documentation. No plugin build, directory submission, Marketplace application, test suite or compatibility test has been run.
- **Open decisions:** Pro Marketplace paid standalone vs freemium listing arrangement; $99/year billing and price parity; vendor application; exact GPL license and policy-compatible upgrade messaging. Submission dates remain estimates and approvals are not guaranteed.


## 2026-11-06 — Phase 1 completed

- **Phase:** 1 — Market and requirements (**Complete, 100%**). Phase 2 remains planned.
- **Completed:** Compared WooCommerce core and seven adjacent products; documented overlap, price evidence where published, product gap and non-goals; expanded the canonical options bank; separated product options from system responsibilities; set the Phase 1 platform baseline; recorded Free/Pro directory submission requirements and release gates.
- **Market evidence:** Public listings/documentation reviewed for Order Workflows, Stale Order Alerts, Failed Order Rescue, Order Priority, Assign Orders, Universal Workflow Automation and Out The Door. Assign Orders displayed $79/year in its Marketplace listing; the planned $99/year Pro price remains a hypothesis, not a validated willingness-to-pay result.
- **Platform baseline recorded:** Target PHP 8.3+; WordPress 7.0+; WooCommerce 11.1+ at the Phase 1 decision date. Current reference matrix is WordPress 7.0/7.1 and WooCommerce 11.1/11.2; refresh to the two latest stable major releases before launch. Current WPML and Polylang reference versions and the HPOS/legacy storage requirements are documented in `docs/PLATFORM-BASELINE.md`.
- **Distribution:** Free-to-WordPress.org and paid-Pro-to-WooCommerce.com tracks are in the README and `docs/DISTRIBUTION-PLAN.md`. Marketplace vendor approval, listing model, billing terms, price parity and reviewer acceptance remain external release gates.
- **Compatibility risk:** Woo Marketplace's current generic guidance still lists PHP 7.4+ while WooCommerce 11.6 is announced to require PHP 8.1+ from January 2027; PHP 8.1 is upstream EOL. The plan targets PHP 8.3+ and requires written Marketplace clarification before submission.
- **CLI constraint:** No CLI task/work item was added. README, AGENTS.md and AI-PLAN now defer all CLI-related work until after the plugin is final.
- **Verification:** Re-read the current GitHub README and planning files; verified official source pages for platform releases, PHP lifecycle, multilingual plugin versions, Marketplace submission rules and competitor feature claims. Documentation/research verification only; no code, tests, installation, performance run or compatibility suite has been executed.
- **Deferred decisions (not blockers for Phase 1):** Customer willingness-to-pay for $99/year; Marketplace paid-standalone vs freemium packaging; final version refresh and Marketplace PHP-policy confirmation at submission time.


## 2026-11-06 — Phase 1 documentation refinement

- Clarified the WooCommerce trademark naming rule: keep WPEssential Order Rescue Hub distinctive and use “for WooCommerce” only as a compatibility descriptor; retain the requested repository slug separately.
- Updated the README Marketplace row to show the PHP 7.4 policy conflict alongside the PHP 8.3+ product target, so the release gate is visible in the phase summary.
- **Phase status remains:** Complete, 100%. No CLI work item was added. No code or tests were run.



## 2026-11-06 — Phase 2 completed

- **Phase:** 2 — Product and UX specification (**Complete, 100%**). Phase 3 remains planned.
- **Completed:** Added `docs/UX-SPECIFICATION.md` with user/jobs, information architecture, exception inbox and case-detail requirements, canonical case states, Pro workflows/assignment/SLA, settings-to-option-bank mapping, React design direction, error/loading/empty states, accessibility, localization, RTL and responsive behavior.
- **Safety and scope:** Kept case state separate from WooCommerce order status; private notes are explicitly internal; MVP excludes payment capture/refund/cancel controls and customer communication; no new option key or CLI task was added.
- **PSR-4:** Added the required PSR-4 PHP runtime autoloader to README, architecture decisions, AI handoff acceptance and repository instructions. Phase 3 must settle namespace root, mapping and loading method; implementation belongs to Phase 5 and must ship without a merchant-run CLI requirement.
- **Research reviewed:** Official WooCommerce extension UX/settings guidance, WordPress Components reference, WordPress admin-menu guidance and WordPress accessibility standards (WCAG 2.2 AA), checked 2026-11-06.
- **Verification:** Re-read the current repository planning files, reviewed official UX/accessibility references, and verified GitHub content writes. Documentation/research verification only. No React UI, PHP code, CLI task, automated test, compatibility test or live-store action was performed.
- **Evidence:** `docs/UX-SPECIFICATION.md`, updated README phase table, `docs/ARCHITECTURE-DECISIONS.md`, `docs/AI-PLAN.md` and `AGENTS.md`.
- **Open for Phase 3:** Data ownership/schema, event lifecycle/idempotency, capability matrix, retention/migration, concrete WordPress/WooCommerce UI components, PSR-4 namespace root/mapping, and runtime autoloader implementation approach.


## 2026-11-06 — Phase 3 completed

- **Phase:** 3 — Architecture and data flow (**Complete, 100%**). Phase 4 remains planned.
- **Completed:** Added `docs/ARCHITECTURE-DATA-FLOW.md` and finalized architecture decisions for case/event storage, exception processing, idempotency/concurrency, REST boundaries, capabilities, privacy/retention, failures/recovery, Pro rules, and multisite scope.
- **Data design:** Selected site-prefixed Hub-owned case and append-only event tables; one case row per order, with multiple reason codes and episode-aware reopening. WooCommerce remains the order source of truth and is accessed through CRUD/query APIs. Proposed 12-month retention for closed cases is explicitly marked for Phase 4 validation.
- **Composer PSR-4:** Specified namespace mapping WPEssential\OrderRescueHub\ to includes/ and the requirement to ship Composer-generated vendor/autoload.php plus required metadata. Merchant installs must not require Composer. No Composer CLI task or other CLI backlog item was added.
- **Research reviewed:** Official WooCommerce HPOS/order query guidance, WordPress custom table guidance, and Composer autoload documentation, checked 2026-11-06.
- **Verification:** Re-read current repository plans, reviewed official primary-source documentation, and verified the new architecture document and planning updates on GitHub. Documentation/research verification only; no code, schema, Composer artifact, CLI task, automated test, compatibility test, or live-store operation was performed.
- **Evidence:** `docs/ARCHITECTURE-DATA-FLOW.md`, updated `docs/ARCHITECTURE-DECISIONS.md`, README phase table, `docs/AI-PLAN.md`, and `AGENTS.md`.
- **Phase 4 validation items:** Final database/index compatibility against the supported MySQL/MariaDB matrix; exact hooks, scheduler cadence and batch sizing; concrete REST/capability matrix; retention defaults; component/build choices and release package contents.


## 2026-11-06 — Phase 4 completed

- **Phase:** 4 — Pre-development package (**Complete, 100%**). Phase 5 remains planned.
- **Completed:** Added `docs/PRE-DEVELOPMENT-PLAN.md` with frozen product constraints, implementation contracts, ordered Free MVP work packages, Definition of Done, threat review, compatibility/release matrix and test scenarios.
- **Compatibility snapshot refreshed:** WordPress 7.1.4; WooCommerce 11.2.2; PHP 8.3 target minimum and 8.3/8.4/8.5 test targets subject to current support; WPML 5.3.0 / String Translation 5.3.0 / WooCommerce Multilingual 5.8.0; Polylang 3.9.1 / Polylang for WooCommerce 2.2.7. The matrix is a plan, not a compatibility claim.
- **Policy note:** WordPress/WooCommerce recommend PHP 8.3+, while Woo Marketplace generic guidance asks for PHP 7.4+ support. Maintain the project’s security-oriented baseline unless Marketplace clarifies its minimum in writing. Verify exact PHP constraints for the multilingual bundle selected for each test.
- **Composer PSR-4:** Standardized mapping WPEssential\\OrderRescueHub\\ to includes/, direct bootstrap load of vendor/autoload.php, and Composer-only class loading. No custom SPL loader, per-class include list, or CLI task/work item was added.
- **Research reviewed:** Official WordPress release/requirements, WooCommerce releases/system requirements and Marketplace guidance, PHP supported-version lifecycle, WPML compatibility/minimum requirements, and Polylang directory/add-on changelog pages (checked 2026-11-06).
- **Verification:** Re-read current repository files, checked official source pages, and verified GitHub documentation writes. Documentation/research verification only; no source implementation, test execution, package build, live-store test, or submission was performed.
- **Evidence:** `docs/PRE-DEVELOPMENT-PLAN.md`, refreshed `docs/PLATFORM-BASELINE.md`, README phase table, `docs/AI-PLAN.md`, `docs/ARCHITECTURE-DATA-FLOW.md`, `docs/ARCHITECTURE-DECISIONS.md` and `AGENTS.md`.
- **Phase 5 is next:** Free MVP implementation according to PRE-01 through PRE-09; no implementation was started as part of this documentation phase.


## 2026-11-06 — Phase 5 Free MVP implementation completed

- **Phase:** 5 — Free MVP implementation (**Complete, 100%**). Phase 6 remains planned.
- **Completed:** Added the WooCommerce admin exception inbox and case details; reason/state/age/order-ID filters and pagination; private notes and case audit events; WooCommerce order detection hooks and bounded scheduled age sweep; Hub-owned schema; REST read/write routes with capabilities and order access checks; Composer PSR-4 runtime mapped to `includes/`; plugin bootstrap, assets, `readme.txt`, and GPL licensing notice.
- **Safety:** Free MVP records internal triage only. It does not capture payments, issue refunds, cancel orders, change WooCommerce order statuses, or send customer messages. Case records store allowlisted evidence rather than customer email/address data.
- **Review fixes:** Repaired malformed escaped line breaks in PHP sources, fixed the Assets namespace import, corrected `$wpdb->update()` format mapping for priority/evidence, fixed pagination state updates, and aligned search/currency labels with implementation.
- **Verification:** Reviewed repository source and metadata; Node syntax check passed for `assets/admin.js`. PHP parsing/runtime, WordPress/WooCommerce installation, HPOS, multilingual, accessibility, security, and performance validation were not run and remain Phase 7/release gates. The local environment did not provide a PHP runtime. No CLI backlog item was added.
- **Package status:** Development package metadata is 0.1.0. This is not a production release and does not claim directory/Marketplace acceptance or compatibility certification.
- **Phase evidence:** `docs/IMPLEMENTATION-FREE-MVP.md`; README phase table updated to 100%.


## 2026-11-06 — Deep audit of Phases 0–6 and Phase 6 completed

- **Phase status:** Phases 0–6 are complete for their documented scope. README phase table updated; Phase 7 compatibility/release validation remains the next open phase.
- **Phase 0–4 audit:** Confirmed product scope, dated market/requirements scan, UX specification, architecture/data-flow baseline and pre-development/test matrix. Corrected current README/architecture text that still claimed no implementation existed; historical phase documents now identify their handoff-time scope. Search promises and option-bank entries were aligned to implemented behavior.
- **Phase 5 audit:** Added priority filtering and direct native WooCommerce order links, aligned order-manager screen/list capabilities with per-order access checks, and added automatic case closure when an evaluated order no longer has an exception. Manual dismissals/duplicates stay closed while the same exception persists; after an exception-clear event, a later recurrence can create a new case episode.
- **Phase 6 delivered:** Pro source under `includes/Pro/` adds opt-in administrator email alerts with dedupe/retry, eligible-staff assignment, owner/SLA filters, calendar/business-hour SLA clocks, first-response and close-time tracking, holidays/pause states, warning/breach history, and bounded versioned workflows. Pro controls are visible only for a Pro build flag; high-impact WooCommerce order actions remain unavailable.
- **SLA audit fixes:** SLA results now use the actual first-note and `closed_at` timestamps rather than a delayed cron scan time; case transitions and assignments immediately synchronize pause/resume/close behavior; reopening starts a fresh case/SLA episode.
- **Lifecycle and permission recheck:** Confirmed `$wpdb->update()` format count/order against the 16 case fields, verified dismissed outcomes remain closed until the exception clears, and verified a later recurrence resets detection/SLA timestamps into a new episode. Kept global case metadata behind `manage_woocommerce` after reviewing cross-order leakage risk; eligible assignees need both manager and order-edit capabilities.
- **Documentation:** Added `docs/PHASE-AUDIT-0-6.md` and `docs/PHASE-6-PRO-IMPLEMENTATION.md`; updated README, AI-08, product scope, architecture/current-status notes, and implementation reports.
- **Verification:** Final `assets/admin.js` parsed successfully in the JavaScript runtime. Source/documentation checks only; PHP runtime parsing, WordPress/WooCommerce install, HPOS, mail delivery, security, accessibility, performance, WPML/Polylang, licensing/package split and Marketplace validation were not run and remain release gates.
- **Build/licensing boundary:** Composer ClassLoader + PSR-4 map is packaged in source and Pro source is gated by `WPERH_PRO_EDITION`; this is not license enforcement. Final Composer artifact reconciliation, paid licensing, production packaging and submissions remain outside Phases 0–6.
- **CLI constraint:** No CLI task, command or backlog item was added.


## 2026-11-07 — Phase 7 started; source audit fixes committed

- **Phase:** 7 — Compatibility and quality (**In progress, 35%**). Start date set to 2026-11-07; original target end remains a planning estimate.
- **Source fixes:** Corrected Pro bootstrap namespace syntax, schema upgrade comparison/version persistence, manual case-transition event dispatch for immediate SLA synchronization, admin timezone/holiday parsing, and rotating bounded SLA/workflow scan windows.
- **Checks actually run:** Repository-wide PHP source pattern scan and focused source review; JavaScript source parsed successfully in the JavaScript runtime after edits.
- **Not run:** PHP lint/runtime, WordPress/WooCommerce install/upgrade tests, HPOS/legacy modes, WPML/Polylang, security/accessibility/performance test suites, or package/submission checks.
- **Constraint:** Repository instructions and the user's standing instruction prohibit CLI tasks before plugin finalization. No CLI task, workflow, command, or CLI-only setup was added or executed. No attached PHP runtime or WordPress test site was available for this pass.
- **Status:** Phase 7 remains incomplete until the execution-based gates listed in `docs/PHASE-7-QUALITY-REPORT.md` have evidence. No compatibility certification or submission readiness is claimed.
