# Architecture decisions (provisional)

## AD-001 — React-based WordPress admin

**Status:** Proposed; Phase 3 decision pending.  
Build an admin application with React using WordPress-provided packages and the platform's established build approach. Bundle assets locally. Do not load framework assets from a CDN. The UX specification must confirm that a React interface is justified for the exception queue and does not duplicate native order detail unnecessarily.

## AD-002 — WooCommerce CRUD and query APIs

**Status:** Required direction; implementation details pending.  
Use WooCommerce order CRUD/query APIs for compatibility across HPOS and legacy storage. Direct order-table or post-meta access is prohibited. Declare HPOS compatibility only after exercising the supported matrix.

## AD-003 — Approval-controlled high-risk actions

**Status:** Safety requirement.  
Default behavior is non-executing. If an action executor is approved later, separate propose/approve/execute; re-read payment/order state just before execution; enforce capability and nonce checks; protect retries with idempotency; show a clear confirmation; persist an audit result; and fail closed on ambiguity.

## AD-004 — Plugin-owned case data

**Status:** Open.  
Phase 3 must compare order metadata versus dedicated plugin tables for case lifecycle, assignment, SLA timestamps, and audit events. Select based on query scale, HPOS compatibility, retention, migration, and uninstall behavior. Never duplicate WooCommerce order fields unnecessarily.


## AD-005 — PSR-4 PHP autoloading

**Status:** Required; namespace and mapping details to be finalized in Phase 3.  
All plugin PHP classes must use a single documented namespace root and predictable PSR-4 directory mapping. The final plugin package must include a runtime autoloader so class loading does not depend on merchant-run commands. Phase 3 selects the namespace root, source layout and loader approach; Phase 5 implements and verifies class-to-file resolution. Do not create a CLI work item for this requirement.
