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
- **Composer PSR-4:** Specified namespace mapping WPEssential\OrderRescueHub\ to src/ and the requirement to ship Composer-generated vendor/autoload.php plus required metadata. Merchant installs must not require Composer. No Composer CLI task or other CLI backlog item was added.
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
