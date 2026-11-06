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
