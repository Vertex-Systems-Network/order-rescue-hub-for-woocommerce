# Market scan and canonical options

See the README for the initial competitor map and the unified options bank. This file tracks verification work required to close Phase 1.

## Research tasks

- [ ] Record date, current version, supported platform versions, pricing model and exact features for each direct/adjacent product.
- [ ] Include official marketplace listing and documentation links; distinguish vendor claims from tested behavior.
- [ ] Compare at least: WooCommerce core, WooCommerce Order Workflows, Stale Order Alerts, Failed Order Rescue, Order Priority, and one general workflow automation product.
- [ ] Search WordPress.org and WooCommerce Marketplace for assignment, SLA, exception queue, shipping-data validation, and payment-recovery products.
- [ ] Map each candidate feature to one canonical option key; identify overlap and intentional exclusions.
- [ ] Validate target users and willingness-to-pay before treating $99/year as final.

## Option-key policy

Use `domain.capability` lower-case keys from the README bank. A key represents a user-visible capability or policy, not a second name for the same feature. Add an entry only with: user problem, edition, owner subsystem, data needed, permission boundary, failure behavior, acceptance criteria, and overlap review. Avoid synonyms such as separate `alerts`, `notifications`, and `reminders` switches when they are one configurable channel capability.
