# Platform and compatibility baseline

**Decision date:** 2026-11-06. This is the Phase 1 planning baseline. Refresh the version numbers before the first release and each submission; compatibility claims require executed tests.

## Planned minimums and test targets

| Area | Phase 1 requirement | Evidence and release rule |
|---|---|---|
| PHP | **Target minimum PHP 8.3** for first public release; recommend the latest PHP branch with upstream security support | WordPress and WooCommerce currently recommend PHP 8.3+. WooCommerce 11.6 is announced to require PHP 8.1+ from January 2027, but PHP 8.1 is already EOL; do not recommend EOL runtimes. Woo Marketplace's current generic submission checklist still says PHP 7.4+ minimum, so obtain vendor clarification before Marketplace submission rather than silently lowering the security baseline |
| WordPress | **Target minimum 7.0**; current stable-major test set 7.0 and 7.1 | As of this decision, 7.1.4 is latest stable patch and 7.0.8 is the prior major branch's latest patch. Marketplace asks for the two latest major releases. Test the latest patch on each supported major; re-evaluate minimum before release |
| WooCommerce | **Target minimum 11.1**; current stable-major test set 11.1 and 11.2 | Current releases include 11.1.2 and 11.2.2. The November 4, 2026 release page labels 11.2.2 stable. Marketplace asks for the two latest major releases. At Phase 7 and submission, refresh because releases continue |
| Database/host | Target WooCommerce's recommended production environment: HTTPS, MySQL 8.0+ or MariaDB 10.6+, and at least 256 MB WordPress memory | The plugin must not raise WooCommerce's hosting baseline without evidence. No direct order-table SQL. Reassess if plugin-owned case storage requires a stronger database requirement |
| Order storage | HPOS is a required compatibility target; legacy post-based order storage tested while WooCommerce supports it | Use WooCommerce CRUD/query APIs only. Do not claim HPOS compatibility until both modes have been tested and the official compatibility declaration is accurate |
| React/admin JS | Use WordPress-provided packages and WordPress i18n APIs; bundle built assets locally | No CDN framework loading. Accessibility and admin interaction patterns are reviewed in Phase 2/7 |
| WPML | Test with WPML Multilingual CMS 5.3.0, String Translation 5.3.0, and WPML Multilingual & Multicurrency for WooCommerce 5.8.0 as current reference set | These versions were listed as released Nov 4, 2026; WPML's compatibility page lists WooCommerce 11.2 as tested. Translate plugin admin strings; no custom WPML coupling unless the plugin later stores translatable content |
| Polylang | Test with Polylang 3.9.1 and Polylang for WooCommerce 2.2.7 as current reference set | Directory listing shows Polylang 3.9.1 tested through WordPress 7.1.4. The WooCommerce add-on changelog shows 2.2.7 (Oct 12, 2026) and adaptations for WooCommerce 11.2. Test the Hub's admin case list and order language context; do not claim vendor certification |
| Distribution package | Free goes to WordPress.org; paid Pro goes to WooCommerce.com Marketplace | See `docs/DISTRIBUTION-PLAN.md`. Current Marketplace technical guidance and WooCommerce's announced PHP 8.1 floor create a PHP-policy conflict to resolve with the vendor before submission |

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
- WooCommerce server recommendations: https://woocommerce.com/document/server-requirements/
- Upcoming PHP requirement for WooCommerce 11.6: https://developer.woocommerce.com/2026/09/29/php-8-1-requirement/
- PHP supported versions: https://www.php.net/supported-versions.php
- WPML versions: https://wpml.org/version/
- WPML/WooCommerce compatibility: https://wpml.org/plugin/woocommerce-2/
- Polylang directory listing: https://wordpress.org/plugins/polylang/
- Polylang for WooCommerce changelog: https://polylang.pro/polylang-for-woocommerce-changelog/
- Woo Marketplace submission requirements: https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product
