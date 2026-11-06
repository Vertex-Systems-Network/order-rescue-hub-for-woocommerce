# Repository instructions for AI coding agents

## Before starting

1. Read `README.md` and `docs/STATUS.md`.
2. Identify the active phase and the requested deliverable. Do not advance a phase by assertion; use its acceptance evidence.
3. Read relevant specifications in `docs/` and check canonical option keys before proposing a setting or subsystem.

## Product rules

- This is a WPEssential add-on for WooCommerce, planned as an exception and resolution workspace.
- Keep order data access on WooCommerce CRUD/query APIs and design for HPOS.
- Use React for the WordPress admin interface. Prefer WordPress-provided packages and patterns; bundle production assets and do not fetch React from a CDN.
- Do not add WP-CLI, shell commands as product features, or a CLI-only install/operation workflow before finalization.
- Payment capture, refunds, cancellations, and payment-implying status changes are sensitive. Never execute silently. Require fresh state checks, capabilities, nonce validation, explicit human approval, idempotency, and audit events if the feature is approved at all.
- Keep external requests, telemetry, customer messages, and licensing services opt-in and separately reviewed.
- Treat WPML and Polylang compatibility as requirements to test, not as a claim to make without evidence.

## Change discipline

- Avoid duplicate settings and overlapping subsystems. Reuse the option-bank key or document why it must be split.
- Do not introduce dependencies, database tables, scheduled jobs, or destructive uninstall behavior without a written decision and migration/rollback plan.
- Keep customer/order information out of logs and test fixtures. Escape output, sanitize input, validate permissions, and minimize stored personal data.
- Update `docs/STATUS.md` with date, changed deliverable, progress evidence, checks actually run, and blockers. Update the README phase table only when the phase status genuinely changes.
- Never state that tests, compatibility checks, audits, or manual QA passed unless they were executed and the result is recorded.
- Keep changes small and reviewable. Describe user-visible effects, data handling, security implications, performance impact, and test evidence in the change summary.

## Tooling boundary

No user-facing CLI is in scope before final plugin readiness. Internal build/test tooling may be proposed in the relevant architecture phase; it must not be required of store administrators and must be documented before adoption.
