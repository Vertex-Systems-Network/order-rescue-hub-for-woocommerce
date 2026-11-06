# AI execution plan and handoff backlog

All assistants must follow `AGENTS.md` and README first. The backlog is tool-agnostic; adapt the wording for ChatGPT, Codex, Claude, Cursor, Gemini, or another assistant without changing product decisions.

## Work items

| ID | Stage | Deliverable | Acceptance evidence |
|---|---|---|---|
| AI-01 | Current | Complete competitor feature/price/version table | Dated primary-source links, features separated from claims, overlap map |
| AI-02 | Requirements | Close canonical options bank and version baseline | No duplicate keys; PHP/WP/WC proposals with official support/test rationale |
| AI-03 | UX | Admin information architecture and React screen specification — Complete 2026-11-06 | `docs/UX-SPECIFICATION.md`: inbox, case detail, settings, workflows, assignment, SLA and empty/error states; keyboard/a11y requirements |
| AI-04 | Architecture | Data model and event-flow design — Complete 2026-11-06 | `docs/ARCHITECTURE-DATA-FLOW.md`: case/event schema, lifecycle, idempotency, query strategy, permissions, retention, migration direction and Composer PSR-4 mapping |
| AI-05 | Threat review | Permission/action abuse analysis — Complete 2026-11-06 | `docs/PRE-DEVELOPMENT-PLAN.md`: capability/nonce/order-access matrix, payment-action boundary, privacy, logging and abuse controls |
| AI-06 | Pre-development | MVP tickets and Definition of Done — Complete 2026-11-06 | `docs/PRE-DEVELOPMENT-PLAN.md`: ordered code work packages, dependencies, Definition of Done, threat review, compatibility and release test matrix; no CLI item |
| AI-07 | Implementation | Free MVP, including bundled Composer PSR-4 runtime autoloader — Complete 2026-11-06 | `docs/IMPLEMENTATION-FREE-MVP.md`: case inbox, detection, notes/history, REST permissions, React UI, includes/ mapping and direct vendor/autoload.php load; Node syntax check and source review recorded; PHP/WP/WC compatibility validation deferred to Phase 7 |
| AI-08 | Pro | Alerts, assignment, workflows, SLA and order-action approval boundary — Complete 2026-11-06 | `docs/PHASE-6-PRO-IMPLEMENTATION.md`: opt-in admin email, assignment without access grants, business/calendar SLA clock, bounded dry-run workflows and disabled high-impact actions; licensing/package/release validation remains in AI-09/Phase 7+ |
| AI-09 | Phase 7 quality | Execute the compatibility and quality matrix | `docs/PHASE-7-QUALITY-REPORT.md`: PHP lint/runtime, WordPress/WooCommerce, HPOS/legacy storage, WPML/Polylang, security, accessibility, performance and packaging evidence; all blockers closed |
| AI-10 | Release | WordPress.org Free submission package and WooCommerce.com paid Pro submission package, tracked as separate review gates | Directory ZIP/readme/GPL/security evidence; vendor approval, billing model, QIT/UX/accessibility/compatibility evidence; reviewer feedback closed for each channel |

## Phase 6 completion note

AI-08 Pro capability source is complete for the documented scope. Pro activation is a paid-build boundary only; it is not a license service. Payment capture, refunds, cancellations and payment-related status actions remain unavailable. Phase 7 and AI-09 own runtime, compatibility, packaging, licensing and submission validation.

## Phase 5 completion note

AI-07 implementation is complete for the planned Free MVP scope. Do not treat this as a production release or compatibility certification. Phase 7 owns PHP runtime, WordPress/WooCommerce/HPOS, WPML/Polylang, accessibility, security and performance validation. No CLI-related backlog item was added.

## Standard handoff prompt

> Work on the WPEssential Order Rescue Hub for WooCommerce repository. Read `AGENTS.md`, `README.md`, `docs/STATUS.md`, and the relevant specification before changing anything. Report the active phase and scope. Preserve canonical option keys and architecture decisions. Do not create, schedule, implement, or run any CLI-related task before the plugin is final. Do not execute payment/refund/cancel actions, send customer communications, add telemetry, or introduce dependencies unless the documented approval gate is satisfied. Make only the deliverable for the active phase, record what changed and what was actually verified in `docs/STATUS.md`, and update the README phase table only when evidence justifies it. Do not claim tests or compatibility checks that were not run.

## Model-specific repository entry points

- **ChatGPT/Codex:** `AGENTS.md` + README, then task-specific docs; summarize changes and actual verification.
- **Claude:** same hierarchy; ask it to inspect all existing docs before proposing edits and not to widen scope.
- **Cursor:** same hierarchy; use `.cursor/rules/` only for concise editor-specific reminders that do not fork canonical rules.
- **Gemini:** same hierarchy; preserve option IDs and report uncertainty/source freshness.
- **Other assistants:** follow the same repository contract; no model may override product safety constraints.

Do not maintain duplicated full instructions in separate model files. This file and `AGENTS.md` are the canonical AI operating contract; future tool-specific files should link back to them and contain only integration-specific behavior.
