# AI execution plan and handoff backlog

All assistants must follow `AGENTS.md` and README first. The backlog is tool-agnostic; adapt the wording for ChatGPT, Codex, Claude, Cursor, Gemini, or another assistant without changing product decisions.

## Work items

| ID | Stage | Deliverable | Acceptance evidence |
|---|---|---|---|
| AI-01 | Current | Complete competitor feature/price/version table | Dated primary-source links, features separated from claims, overlap map |
| AI-02 | Requirements | Close canonical options bank and version baseline | No duplicate keys; PHP/WP/WC proposals with official support/test rationale |
| AI-03 | UX | Admin information architecture and React screen specification — Complete 2026-11-06 | `docs/UX-SPECIFICATION.md`: inbox, case detail, settings, workflows, assignment, SLA and empty/error states; keyboard/a11y requirements |
| AI-04 | Architecture | Data model and event-flow design | Sequence/lifecycle, idempotency, query strategy, retention, migration and PSR-4 namespace/autoloader decision |
| AI-05 | Threat review | Permission/action abuse analysis | Capability/nonce matrix, payment-action confirmation boundary, privacy and log redaction rules |
| AI-06 | Pre-development | MVP tickets and Definition of Done | Small ordered backlog with acceptance criteria and dependencies |
| AI-07 | Implementation | Free MVP, including bundled PSR-4 runtime autoloader | Namespaced classes resolve through documented PSR-4 mapping in the distributed package; reproducible verification evidence and updated status log |
| AI-08 | Pro | Alerts, assignment, workflows, SLA | Opt-in controls, dry-run, audit, failure/retry and pricing validation |
| AI-09 | Release | WordPress.org Free submission package and WooCommerce.com paid Pro submission package, tracked as separate review gates | Directory ZIP/readme/GPL/security evidence; vendor approval, billing model, QIT/UX/accessibility/compatibility evidence; reviewer feedback closed for each channel |

## Standard handoff prompt

> Work on the WPEssential Order Rescue Hub for WooCommerce repository. Read `AGENTS.md`, `README.md`, `docs/STATUS.md`, and the relevant specification before changing anything. Report the active phase and scope. Preserve canonical option keys and architecture decisions. Do not create, schedule, implement, or run any CLI-related task before the plugin is final. Do not execute payment/refund/cancel actions, send customer communications, add telemetry, or introduce dependencies unless the documented approval gate is satisfied. Make only the deliverable for the active phase, record what changed and what was actually verified in `docs/STATUS.md`, and update the README phase table only when evidence justifies it. Do not claim tests or compatibility checks that were not run.

## Model-specific repository entry points

- **ChatGPT/Codex:** `AGENTS.md` + README, then task-specific docs; summarize changes and actual verification.
- **Claude:** same hierarchy; ask it to inspect all existing docs before proposing edits and not to widen scope.
- **Cursor:** same hierarchy; use `.cursor/rules/` only for concise editor-specific reminders that do not fork canonical rules.
- **Gemini:** same hierarchy; preserve option IDs and report uncertainty/source freshness.
- **Other assistants:** follow the same repository contract; no model may override product safety constraints.

Do not maintain duplicated full instructions in separate model files. This file and `AGENTS.md` are the canonical AI operating contract; future tool-specific files should link back to them and contain only integration-specific behavior.
