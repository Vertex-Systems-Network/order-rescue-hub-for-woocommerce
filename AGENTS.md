# Repository instructions for AI coding agents

## Before starting

1. Read `README.md`, `AGENTS.md`, and the current checkpoint at the top of `docs/STATUS.md`.
2. Treat `docs/AI-PLAN.md` as the canonical executable backlog and `docs/PHASE-7-QUALITY-REPORT.md` as the gate evidence ledger. Append-only status entries below the latest checkpoint are historical evidence, not current state.
3. Identify the active phase and the first open, unblocked, highest-priority task. Do not advance a phase by assertion; use its acceptance evidence.
4. Read relevant specifications in `docs/` and check the canonical options bank before proposing a setting or subsystem.
5. When repository documents disagree, use the newest dated current checkpoint and executable gate table; correct the conflicting document in the same change instead of copying the conflict forward.

## Product rules

- This is a WPEssential add-on for WooCommerce, implemented as an exception and resolution workspace; read README and phase reports for current development and release status.
- Keep order data access on WooCommerce CRUD/query APIs and design for HPOS.
- Use React for the WordPress admin interface. Prefer WordPress-provided packages and patterns; bundle production assets and do not fetch React from a CDN.
- Use Composer PSR-4 mapping WPEssential\OrderRescueHub\ to includes/ for plugin PHP classes; the bootstrap directly loads Composer's generated vendor/autoload.php. Composer is the only class loader; do not add a handwritten SPL loader or per-class include list. Ship required generated metadata; the merchant must not need Composer installed.
- Do not create, schedule, implement, or run any CLI-related task before the plugin is final. Do not add WP-CLI commands or CLI-only merchant setup; revisit only after finalization.
- Payment capture, refunds, cancellations, and payment-implying status changes are sensitive. Never execute silently. Require fresh state checks, capabilities, nonce validation, explicit human approval, idempotency, and audit events if the feature is approved at all.
- Keep external requests, telemetry, customer messages, and licensing services opt-in and separately reviewed.
- Treat standard WordPress localization, locale-aware formatting, and RTL behavior as requirements; WPML and Polylang integrations are out of scope by owner decision.
- Pro source is gated by the paid build flag and is not license validation. Keep high-impact order actions unavailable until a separate approval design is reviewed and implemented.

## Change discipline

- Avoid duplicate settings and overlapping subsystems. Reuse the option-bank key or document why it must be split.
- Do not introduce dependencies, database tables, scheduled jobs, or destructive uninstall behavior without a written decision and migration/rollback plan.
- Keep customer/order information out of logs and test fixtures. Escape output, sanitize input, validate permissions, and minimize stored personal data.
- Update `docs/STATUS.md` with date, changed deliverable, progress evidence, checks actually run, and blockers. Update the README phase table only when the phase status genuinely changes.
- Never state that tests, compatibility checks, audits, or manual QA passed unless they were executed and the result is recorded.
- Keep changes small and reviewable. Describe user-visible effects, data handling, security implications, performance impact, and test evidence in the change summary.

## Execution and completion contract

- Treat `docs/AI-PLAN.md` as the executable backlog and follow the first eligible, highest-priority open task in the active phase. After one task passes, immediately continue to the next eligible task in the same work session; a successful intermediate step is not a reason to stop.
- A user message containing `...`, `continue`, or an equivalent continuation means resume the next open task. Do not reply with a status-only update when an unblocked implementation, review, CI run, package build, or authorized staging check can be performed.
- Keep progress messages brief and action-based. Do not repeatedly narrate the plan. Send a final report only after completing the available work in the current run, or when a real external dependency prevents the next required action.
- A blocker must name the exact missing dependency, why it cannot be resolved with available repository, CI, browser, or connected-app access, and which independent backlog tasks were completed while it was blocked. Do not treat an out-of-scope integration as a blocker.
- Do not use subjective completion percentages. For a phase with defined binary gates, calculate progress as fully accepted gates divided by total gates; partial gates count as open. If a gate list is not defined, report gate states without a percentage.
- Separate code/quality readiness from external directory or Marketplace review. WordPress.org and WooCommerce.com submission or approval gates belong to their own phases and must not keep an otherwise accepted engineering phase artificially open.
- Preserve a precise continuation checkpoint in `docs/STATUS.md`: active task ID, last verified commit/build, completed evidence, next action, and any true external blocker. Another agent must resume without asking the user to restate the task.

## Tooling boundary

Before final plugin readiness, do not create a user-facing WP-CLI command, CLI-only merchant setup, or planned product/development work item whose deliverable is a CLI feature. Automated CI/build workflows may use shell commands and PHP/Node runtimes as internal verification mechanics; these commands are not plugin features or CLI backlog tasks. Do not expose CI commands as merchant requirements.

## Artifact handoff boundary

Do not attach or provide plugin ZIPs or other project files in chat. When an authorized test-site upload is part of the task, use the connected browser flow, verify the installed build identity, then delete temporary local package copies. If browser upload fails, do not repeat the same failing flow in a loop: keep the artifact in GitHub Actions, record the exact failure, continue independent code/CI/docs work, and request a manual site action only when no available route can complete the required installation.
