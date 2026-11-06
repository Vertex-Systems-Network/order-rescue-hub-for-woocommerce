# Architecture decisions

Phase 3 decisions are based on the product/UX requirements and the WooCommerce, WordPress, and Composer references recorded in [ARCHITECTURE-DATA-FLOW.md](ARCHITECTURE-DATA-FLOW.md).

## AD-001 — React-based WordPress admin

**Status:** Accepted direction; concrete component versions and implementation details are Phase 4.  
Use React with WordPress-provided packages and WordPress/WooCommerce admin patterns. Bundle production assets locally; do not load React from a CDN. The React interface serves the exception queue and case workflow; full order management stays in the native WooCommerce order screen.

## AD-002 — WooCommerce CRUD and query APIs

**Status:** Required.  
All order reads and queries use supported WooCommerce CRUD/query APIs, including WC_Order, wc_get_order, wc_get_orders, and WC_Order_Query. Direct order-table, posts, and postmeta access for WooCommerce order facts is prohibited. Declare HPOS compatibility only after the supported matrix is exercised.

## AD-003 — Approval-controlled high-risk actions

**Status:** Safety requirement; high-impact execution is outside MVP.  
Default behavior is non-executing. If an order action is approved in a later phase, separate propose/approve/execute; re-read order/payment state immediately before execution; enforce user capability and nonce checks; protect retries with idempotency; show order-specific consequences; require explicit human confirmation; persist an audit result; and fail closed on ambiguity. Workflows cannot bypass this gate.

## AD-004 — Plugin-owned case and event tables

**Status:** Accepted.  
Use site-prefixed Hub-owned case and append-only event tables. One case row per order stores the lifecycle and current reason/evidence summary; events record attributed changes and deduplicated system activity. WooCommerce remains authoritative for order data. Use WordPress schema upgrade conventions and versioned, repeatable migrations. Exact schema and privacy behavior are in ARCHITECTURE-DATA-FLOW.md.

## AD-005 — Composer PSR-4 autoloading

**Status:** Required and specified.  
Use Composer mapping WPEssential\OrderRescueHub\ to includes/. Ship Composer's generated vendor/autoload.php and the required generated metadata in the production plugin package. The plugin bootstrap loads it; merchant servers do not need Composer installed. Do not maintain a parallel handwritten SPL loader or per-class include list. Load vendor/autoload.php directly from the plugin bootstrap. The release artifact must be self-contained for class loading.

## AD-006 — Event-driven detection with bounded catch-up

**Status:** Accepted direction; exact hooks and cadence are Phase 4.  
Use lightweight WooCommerce signals to mark candidates, re-fetch current state via CRUD APIs, and evaluate exceptions in bounded work. Use a paginated, locked WordPress scheduled sweep as eventual catch-up for age-based rules and missed signals. It is not a real-time SLA guarantee; show actual observation/last-run times.

## AD-007 — REST contract and capability checks

**Status:** Accepted direction; endpoint/capability matrix details are Phase 4.  
Expose only explicit, versioned routes for queue, case details/history, notes, transitions, and Pro settings/assignment. Authenticate admin requests; check nonce and the relevant capability on each request; check per-order access before exposing protected details. Nonce is CSRF protection, not authorization.

## AD-008 — Data minimization and retention

**Status:** Proposed policy for Phase 4 validation.  
Do not persist full order/customer/payment snapshots. Keep reason codes and allow-listed evidence only. Open cases are not time-purged. Proposed closed-case/event retention is 12 months. Keep Hub tables on plugin deletion by default; expose a clearly labeled explicit purge choice, off by default. Integrate applicable WordPress privacy exporter/eraser behavior.

## AD-009 — Pro rules and activity records

**Status:** Accepted direction.  
Store small, validated rule definitions in versioned WordPress options. Store rule run, alert delivery, assignment, and SLA transitions in the case event history. Add a dedicated run table only if measured scale justifies a new migration.

## AD-010 — Multisite boundary

**Status:** Accepted for initial release.  
Use per-site options and per-site prefixed tables. Network-wide settings/reporting are out of initial scope unless Phase 4 documents a validated need.
