# AI plan and repository deep-audit findings

**Audit date:** 2026-11-10  
**Audited repository:** `Vertex-Systems-Network/wpessential-order-rescue-hub-woocommerce`  
**Audited runtime source:** `main` at `402227a95df6a4f173502c27574fa689a8f4be0b`  
**Purpose:** Reconcile the plan and agent instructions with current source, CI evidence, staging evidence, and the user's delivery constraints. This is an engineering/source review, not a third-party security certification.

## Audit findings and repairs

| ID | Finding | Evidence | Repair/status |
|---|---|---|---|
| AUD-01 | Current progress was contradictory: README showed 5/10, 50%, and 40%; separate status paragraphs referred to Build 50/51, while recent history had progressed past those builds. | README current table/paragraph and status log entries dated Nov 10; Git history through Build 67. | Current summaries reconciled to the gate ledger. Older dated entries remain historical and are not current snapshots. |
| AUD-02 | AI-01 competitor research and AI-02 options/platform work lacked completion states although their deliverables and Phase 1 checklist were present. | `docs/MARKET-AND-OPTIONS.md`, `docs/PLATFORM-BASELINE.md`, `docs/DISTRIBUTION-PLAN.md`, and README's canonical options bank. | Marked complete for planning scope; launch-time market, version, pricing, and vendor-policy checks remain later release work. |
| AUD-03 | “No CLI” wording was broad enough to be read as banning all shell/PHP CLI mechanics, despite the required GitHub Actions PHP lint and smoke suite. | AGENTS tooling boundary and existing CI workflow. | Clarified: no WP-CLI feature, merchant CLI workflow, or CLI product backlog item before final readiness; automated CI use of shell/PHP/Node runtimes remains allowed. |
| AUD-04 | Prior agent instructions allowed a verified ZIP to be attached as fallback after browser upload failure, conflicting with the user's no-file-in-chat instruction and enabling repeated upload loops. | AI-PLAN execution step 5 and current delivery preference. | Removed file-in-chat fallback. Keep package artifacts in GitHub Actions, do not repeat the same failed upload, and continue independent work. |
| AUD-05 | P7-05 was still shown open after the latest green security matrix. | CI run [42712129164](https://github.com/Vertex-Systems-Network/wpessential-order-rescue-hub-woocommerce/actions/runs/42712129164), source commit `402227a95df6a4f173502c27574fa689a8f4be0b`; all five jobs passed. | P7-05 is complete for its stated CI acceptance: subscriber denial, invalid filters, scoped cross-order access, nonce filter behavior, mutation guards, and forbidden-action scan. This is not an independent security certification or live HTTP penetration test. |
| AUD-06 | Detector behavior does not meet the product's false-positive requirement; the implementation lacks the documented payment-age configuration. | `includes/Service/Detector.php::evaluate()` assigns `pending_payment` to every `pending` order once age is at least one hour and only records the gateway ID as evidence. `includes/Pro/Settings.php` has no detection-threshold setting. `tests/DetectorSmokeTest.php` has no pending-payment age/payment-method boundary cases. Product/UX/market docs say valid delayed/offline payment must not be treated as failure. | Reopened P7-01. Define a conservative documented default; provide the planned configurable threshold in the correct edition; avoid display labels or undocumented gateway internals; test below/at/above threshold, delayed/offline examples, On hold, and other detector reasons. Update the canonical option key and UX/settings specification with the implementation. |
| AUD-07 | Phase 7 quality report mixed early historical test states with the current checklist. | Report referenced Build 27/88% alongside later Build 50/51 evidence. | Replaced the current summary/table with the ten-gate ledger and latest run evidence. Dated build notes remain historical. |
| AUD-08 | The completion contract did not define how to handle newly discovered acceptance gaps or documentation conflicts. | `AGENTS.md` and `docs/AI-PLAN.md`. | Added source-of-truth precedence, same-change reconciliation, evidence-based gate reopening, artifact-delivery boundaries, and continuation rules. |

## Current evidence snapshot

- CI run **42712129164** succeeded for commit `402227a95df6a4f173502c27574fa689a8f4be0b`; all five jobs passed, including package build and all four WordPress/WooCommerce compatibility cells.
- P7-05 is closed for its automated acceptance evidence.
- P7-01 is open again because every pending order is currently flagged after one hour, with no configurable age setting or test coverage for delayed/offline-payment behavior. This is a confirmed source/spec/test gap and has not yet been fixed.
- Current phase count is **5/10 complete (50%)**. Open gates: P7-01, P7-07, P7-08, P7-09, P7-10.
- Last browser-confirmed staging install remains **Build 50**. Build 67 is CI-green but not confirmed installed on the test site; no Build 67 live behavior is claimed.
- WooCommerce Status identifies the authorized `/el-test` environment type as production. Keep test actions reversible/read-only where possible and avoid real payment, refund, cancellation, customer-message, and stock paths.
- Do not attach a plugin ZIP or project file in chat. Delete temporary local package copies after an upload/test attempt; retain GitHub Actions artifacts as the traceable build record.
- No CLI product feature or CLI backlog task was added. CI shell/PHP commands are internal verification mechanics.

## Corrected continuation contract for every AI agent

1. Read this file, `AGENTS.md`, README's current status, `docs/STATUS.md`'s current checkpoint, and `docs/AI-PLAN.md`.
2. Work the first open gate by priority. Start with P7-01: reconcile product wording, the single canonical option bank, UX/settings design, detector code, and regression cases. Do not edit the plan and then report the code issue as solved.
3. Run the existing CI path after source changes; inspect every job and save exact run/artifact IDs in the status log. A green package build proves packaging only, not live staging behavior.
4. Continue directly to accessibility, performance, Pro runtime, and separate Free/Pro engineering package gates as prior gates close.
5. If site upload fails, record the exact failure once, keep the artifact in Actions, continue independent work, and do not attach the file in chat or repeat the same browser attempt.
6. Keep WordPress.org Free review and WooCommerce.com Pro review as separate later submission/approval phases. Do not claim approval before an actual decision.
7. Before marking a gate complete, verify the latest source, test evidence, and gate definition agree; update README, AI-PLAN, status checkpoint, and Phase 7 report together.
