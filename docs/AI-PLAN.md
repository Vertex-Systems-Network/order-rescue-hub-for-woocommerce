# AI execution plan and handoff backlog

All assistants must follow `AGENTS.md`, README, and the current checkpoint in `docs/STATUS.md`. Review `docs/AI-PLAN-AUDIT.md` for the latest reconciled source/spec risks. The backlog is tool-agnostic; adapt the wording for ChatGPT, Codex, Claude, Cursor, Gemini, or another assistant without changing product decisions.

## Work items

| ID | Stage | Deliverable | Acceptance evidence |
|---|---|---|---|
| AI-01 | Requirements — Complete 2026-11-06 | Competitor feature/price/version table and differentiation scan | `docs/MARKET-AND-OPTIONS.md`: dated public sources, vendor claims separated from verified facts, overlap map; revalidate prices/features before launch |
| AI-02 | Requirements — Complete for planning scope 2026-11-06 | Canonical options/systems bank, platform baseline, and Free/Pro/distribution requirements | `README.md` unified options bank; `docs/PLATFORM-BASELINE.md`; `docs/DISTRIBUTION-PLAN.md`; unresolved vendor policy/pricing decisions remain explicit release gates |
| AI-03 | UX | Admin information architecture and React screen specification — Complete 2026-11-06 | `docs/UX-SPECIFICATION.md`: inbox, case detail, settings, workflows, assignment, SLA and empty/error states; keyboard/a11y requirements |
| AI-04 | Architecture | Data model and event-flow design — Complete 2026-11-06 | `docs/ARCHITECTURE-DATA-FLOW.md`: case/event schema, lifecycle, idempotency, query strategy, permissions, retention, migration direction and Composer PSR-4 mapping |
| AI-05 | Threat review | Permission/action abuse analysis — Complete 2026-11-06 | `docs/PRE-DEVELOPMENT-PLAN.md`: capability/nonce/order-access matrix, payment-action boundary, privacy, logging and abuse controls |
| AI-06 | Pre-development | MVP tickets and Definition of Done — Complete 2026-11-06 | `docs/PRE-DEVELOPMENT-PLAN.md`: ordered code work packages, dependencies, Definition of Done, threat review, compatibility and release test matrix; no CLI item |
| AI-07 | Implementation | Free MVP, including bundled Composer PSR-4 runtime autoloader — Complete 2026-11-06 | `docs/IMPLEMENTATION-FREE-MVP.md`: case inbox, detection, notes/history, REST permissions, React UI, includes/ mapping and direct vendor/autoload.php load; Node syntax check and source review recorded; PHP/WP/WC compatibility validation deferred to Phase 7 |
| AI-08 | Pro | Alerts, assignment, workflows, SLA and order-action approval boundary — Complete 2026-11-06 | `docs/PHASE-6-PRO-IMPLEMENTATION.md`: opt-in admin email, assignment without access grants, business/calendar SLA clock, bounded dry-run workflows and disabled high-impact actions; licensing/package/release validation remains in AI-10/Phase 8+ |
| AI-09 | Phase 7 quality | Execute the compatibility and quality matrix to its explicit exit gates | `docs/PHASE-7-QUALITY-REPORT.md`: binary gate checklist with evidence, owner, and next action; no subjective percentage or staging-upload loop |
| AI-10 | Release | WordPress.org Free submission package and WooCommerce.com paid Pro submission package, tracked as separate review gates | Directory ZIP/readme/GPL/security evidence; vendor approval, billing model, QIT/UX/accessibility/compatibility evidence; reviewer feedback closed for each channel |

## Phase 6 completion note

AI-08 Pro capability source is complete for the documented scope. Pro activation is a paid-build boundary only; it is not a license service. Payment capture, refunds, cancellations and payment-related status actions remain unavailable. Phase 7 and AI-09 own runtime, compatibility, packaging, licensing and submission validation.

## Phase 5 completion note

AI-07 implementation is complete for the planned Free MVP scope. Do not treat this as a production release or compatibility certification. Phase 7 owns PHP runtime, WordPress/WooCommerce/HPOS, WPML/Polylang, accessibility, security and performance validation. No CLI-related backlog item was added.

## Execution protocol (applies to all models)

**Status precedence:** The README phase table, this file's gate table, and the current checkpoint at the top of `docs/STATUS.md` are the only current-state summaries. The remaining entries in `docs/STATUS.md` and historical sections in reports are append-only evidence; never copy their old percentages/build numbers into current status. If a source audit invalidates a gate, reopen that gate and explain why in the same commit. If docs disagree, reconcile them before stopping.

1. Read `AGENTS.md`, `README.md`, `docs/STATUS.md`, this backlog, and the active phase specification. Inspect Git status/history and the latest build before editing.
2. Select the first open, unblocked task ID in the active phase. Do the work now; do not spend a turn merely describing a plan that is already authorized.
3. After an acceptance check passes, update the evidence and immediately proceed to the next open task in the same phase. Do not stop after a progress message, uploaded ZIP, green workflow, or a single staging screen.
4. Run the repository's existing CI/build path for source changes. Do not claim tests, platform compatibility, or UX behavior not directly observed. Make a fresh artifact whenever runtime files change.
5. Use the authorized `/el-test` site for scoped plugin QA. Never touch payment, refund, cancellation, real-customer, or stock-affecting paths. Do not attach project ZIPs/files in chat. If browser upload is unavailable, record the exact failure, retain the artifact in GitHub Actions, continue independent code/CI/documentation tasks, and ask for a manual upload only if installation is still essential and no connected route can complete it. Do not repeat an identical failed upload attempt.
6. Keep repository documentation current and commit/push completed increments to `main` when authorized. Preserve the no-CLI boundary: do not add a merchant CLI feature or a CLI backlog task before final readiness.
7. Continue all work possible with current access. Ask for user action only for a required account/site action or decision outside available permissions. State the exact action and why it is required; continue every independent task.

## Standard handoff prompt

> Continue autonomous development of WPEssential Order Rescue Hub for WooCommerce. Read `AGENTS.md`, `README.md`, `docs/STATUS.md`, `docs/AI-PLAN.md`, and the active phase specification. Inspect Git status/history and the latest staging build. Take the first open, unblocked backlog task; implement and verify it; record evidence and commit/push when authorized; then move directly to the next open task without waiting for another user prompt. A message of `...` means continue. Never send a status-only response while authorized work remains. Do not add merchant CLI tasks/features before final readiness. Do not execute payment/refund/cancel actions, send customer messages, add telemetry, or introduce dependencies outside documented approval. Use evidence-based gate status; do not invent completion percentages. Stop only when phase exit criteria are met or a concrete external blocker prevents all remaining tasks, and describe that blocker precisely.

## Phase 7 executable gates

Use this priority order; a gate is complete only when every criterion in its acceptance evidence is recorded. Partial evidence does not count toward the progress numerator. Equal-weight gate progress is completed gates / 10. Current count: 5/10. P7-01 is deliberately reopened because new source evidence invalidated the previous business-behavior coverage; this is not a new percentage estimate. WordPress.org and WooCommerce.com submission/review are separate later phases, not Phase 7 engineering gates.

| ID | Gate | Current state | Owner | Acceptance evidence | Next action |
|---|---|---|---|---|---|
| P7-01 | PHP lint, core detection behavior, and regression matrix | Open — reopened by 2026-11-10 source audit | Agent | PHP lint and smoke matrix pass on PHP 8.3/8.4/8.5; pending-payment policy has a documented safe default and configurable threshold where promised; tests cover below/at/above threshold, valid delayed/offline-payment cases, On hold behavior, and all detector reasons without false positives; CI passes | Resolve detector/spec gap before proceeding to lower-priority P7 gates; update option bank, UX, tests, and implementation together |
| P7-02 | Supported WordPress/WooCommerce compatibility matrix | Complete | Agent | Four L-1 cells (WP 7.0.8/7.1.4 × WC 11.1.2/11.2.2) clean-install and activate; schema, admin callback, REST denial, populated WooCommerce order detail and 1.0.0→1.1.0 schema upgrade pass on each cell | Run 42708829994 passed all four cells, upgrade fixture, populated CRUD/order detail and package job; preserve matrix on runtime source changes |
| P7-03 | HPOS and legacy storage runtime | Complete | Agent | Inbox and case detail verified in both modes on the authorized site; HPOS restored; single-environment limitation recorded | Reopen only if order access paths change |
| P7-04 | Schema migration and idempotency | Complete | Agent | WordPress/MySQL integration migration 1.0.0→1.1.0 passes; required columns/indexes persist; repeat run is unchanged | Reopen only when schema/migration code changes |
| P7-05 | REST/security acceptance matrix | Complete — CI 42712129164 | Agent | Subscriber denial, scoped order access, invalid input, REST auth nonce behavior, stale/concurrent mutation guards, and sensitive-action absence checks pass in the four-cell matrix and static guard | Preserve the checks on runtime changes; do not overstate this as an independent security certification or live HTTP penetration test |
| P7-06 | WPML/Polylang agreed dry-run | Complete | Agent | Static review confirms no hard dependency/direct vendor API assumptions; translation-ready strings and WooCommerce CRUD identity documented; user-approved dry-run scope recorded | No vendor runtime required unless user later supplies the plugins |
| P7-07 | Accessibility acceptance | Open | Agent | Keyboard-only workflows, screen-reader names/state, focus, contrast, zoom/reflow, reduced motion, error/empty states checked against the documented target | Finish remaining admin UI review; fix and recheck failures |
| P7-08 | Performance acceptance | Open | Agent | CI synthetic query budget plus bounded WooCommerce order-scan and representative staging latency/memory baseline recorded | Extend beyond the case-table-only synthetic query |
| P7-09 | Pro runtime acceptance | Open | Agent | Paid-build UI, settings persistence, assignment, SLA transitions, workflow execution/idempotency/retry and alert delivery tested in isolated Pro fixture; sensitive order actions remain unavailable | Use an isolated Pro fixture; CI stubs alone do not close this gate |
| P7-10 | Free/Pro engineering package acceptance | Open | Agent | Free and Pro package contents audited separately; Composer runtime and translation assets present; build identity, exclusions, clean install/upgrade and hashes recorded; no directory approval implied | Build and inspect both edition artifacts; use the authorized test site when the browser flow works; keep artifacts in Actions and never attach them in chat |

## Model-specific repository entry points

- **ChatGPT/Codex:** `AGENTS.md` + README, then task-specific docs; summarize changes and actual verification.
- **Claude:** same hierarchy; ask it to inspect all existing docs before proposing edits and not to widen scope.
- **Cursor:** same hierarchy; use `.cursor/rules/` only for concise editor-specific reminders that do not fork canonical rules.
- **Gemini:** same hierarchy; preserve option IDs and report uncertainty/source freshness.
- **Other assistants:** follow the same repository contract; no model may override product safety constraints.

Do not maintain duplicated full instructions in separate model files. This file and `AGENTS.md` are the canonical AI operating contract; future tool-specific files should link back to them and contain only integration-specific behavior.
