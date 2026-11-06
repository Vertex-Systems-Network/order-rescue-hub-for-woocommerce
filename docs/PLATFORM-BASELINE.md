# Platform and compatibility baseline

**Snapshot date:** 2026-11-06. This Phase 4 baseline supersedes the Phase 1 snapshot. Phase 7 started 2026-11-07; source-level fixes are recorded in `docs/PHASE-7-QUALITY-REPORT.md`, but the matrix below remains a target, not completed test evidence. Refresh versions before release/submission; compatibility claims require executed tests.

## Planned minimums and test targets

| Area | Current target | Evidence and release rule |
|---|---|---|
| PHP | **Target minimum PHP 8.3** for first public release; test current maintained branches 8.3, 8.4 and 8.5 where supported | WordPress and WooCommerce recommend PHP 8.3+. PHP 8.3 remains security-supported through 2027-12-31; PHP 8.4 through 2028-12-31; PHP 8.5 through 2029-12-31 (refresh before release). Woo Marketplace asks products to support PHP 7.4+ while the project's security-oriented minimum is 8.3; obtain written policy clarification rather than lowering the baseline without approval. Some WPML release notes indicate PHP 8.4 support floor changes; verify each selected multilingual bundle's own support matrix. |
| WordPress | **Target minimum 7.0**; current stable-major test set 7.0 and 7.1 | WordPress 7.1.4 is current stable (2026-10-28); test the latest patch on both supported major branches, 7.0 and 7.1. Marketplace asks for the two latest major releases. Refresh exact patches before each release. |
| WooCommerce | **Target minimum 11.1**; current stable-major test set 11.1 and 11.2 | WooCommerce 11.2.2 is the latest stable patch (2026-11-04); test the latest patch on both supported major branches, 11.1 and 11.2. WPML lists WooCommerce 11.2 as tested. Refresh at Phase 7 and before submission. |
| Database/host | Recommended environment: HTTPS, MySQL 8.0+ or MariaDB 10.11+; do not advertise a Hub-specific database floor until schema tests support it | WordPress recommends MySQL 8.0+ or MariaDB 10.11+; WooCommerce documentation lists MySQL 8.0+ or MariaDB 10.6+ recommendations. Use the stricter WordPress-recommended environment for the test baseline. Validate custom table schema/indexes in Phase 7. |
| Order storage | HPOS is a required compatibility target; legacy post-based order storage tested while WooCommerce supports it | Use WooCommerce CRUD/query APIs only. Do not claim HPOS compatibility until both modes have been tested and the official compatibility declaration is accurate |
| React/admin JS | Use WordPress-provided packages and WordPress i18n APIs; bundle built assets locally | No CDN framework loading. Accessibility and admin interaction patterns are reviewed in Phase 2/7 |
| WPML | Reference bundle: WPML CMS 5.3.0, String Translation 5.3.0, WooCommerce Multilingual 5.8.0 | Listed as released Nov 4, 2026; WPML compatibility page lists WooCommerce 11.2 as tested. Test admin strings, language context and case uniqueness. Verify PHP eligibility for each exact bundle before selecting matrix cells; do not infer current PHP range from old release notes. |
| Polylang | Reference bundle: Polylang 3.9.1 and Polylang for WooCommerce 2.2.7 | Polylang 3.9.1 is tested through WordPress 7.1.4; its WooCommerce add-on changelog lists compatibility changes for WooCommerce 11.2. Test admin case listing and order language context; no vendor certification claim. |
| Distribution package | Free goes to WordPress.org; paid Pro goes to WooCommerce.com Marketplace | See `docs/DISTRIBUTION-PLAN.md`. Current Marketplace technical guidance and WooCommerce's announced PHP 8.1 floor create a PHP-policy issue to resolve with the vendor before submission |

## Translation behavior

- All PHP and React UI strings use the stable text domain and WordPress translation functions/packages. Include contextual and plural strings; translate notices, settings, labels, date/number display and accessibility labels.
- Internal resolution notes remain private. They must never be passed into customer-facing order notes or order emails by default.
- The case queue should show the order's language as context when the multilingual integration exposes it, while keeping UI language governed by the administrator's WordPress locale.
- Do not create translated order copies or duplicate case records per language. Case identity remains anchored to the WooCommerce order and exception reason.
- WPML and Polylang are optional integrations, not activation dependencies. Test alongside their WooCommerce add-ons, but the Hub must remain useful without either multilingual product.

## Release test matrix to refresh at Phase 7

At minimum test the package on the latest stable patch of the then-current two major WordPress and WooCommerce branches, with PHP at the chosen minimum and the latest supported runtime. Include both HPOS and legacy order storage where supported. Test the WPML and Polylang combinations above against the current versions at test time. Include activation/deactivation, upgrade from the prior release, missing dependency handling, admin capability boundaries, duplicate events, empty/error states, long-running queries, and multilingual order listing.

This matrix is a requirement, not a claim that the plugin has been tested. No plugin code or compatibility test exists yet.

## Current official references

- WordPress release archive: https://wordpress.org/download/releases/
- WordPress server requirements / PHP recommendation: https://wordpress.org/about/requirements/
- WordPress 7.1 server compatibility: https://make.wordpress.org/hosting/handbook/server-environment/
- WooCommerce stable releases: https://developer.woocommerce.com/releases/
- WooCommerce server recommendations: https://woocommerce.com/document/update-php-wordpress/
- WPML minimum requirements: https://wpml.org/home/minimum-requirements/
- Upcoming PHP requirement for WooCommerce 11.6: https://developer.woocommerce.com/2026/09/29/php-8-1-requirement/
- PHP supported versions: https://www.php.net/supported-versions.php
- WPML versions: https://wpml.org/version/
- WPML/WooCommerce compatibility: https://wpml.org/plugin/woocommerce-2/
- Polylang directory listing: https://wordpress.org/plugins/polylang/
- Polylang for WooCommerce changelog: https://polylang.pro/polylang-for-woocommerce-changelog/
- Woo Marketplace submission requirements: https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product
