# Platform and compatibility baseline

**Snapshot date:** 2026-11-10. Phase 7 compatibility targets are based on official L-1 guidance and the latest stable releases found on this date. This baseline defines what CI must test; version mentions below are targets, not claims that the matrix has already passed. Refresh exact patches before release/submission.

## Planned minimums and test targets

| Area | Current target | Evidence and release rule |
|---|---|---|
| PHP | **Target minimum PHP 8.3** for first public release; test current maintained branches 8.3, 8.4 and 8.5 where supported | WordPress and WooCommerce recommend PHP 8.3+. PHP 8.3 remains security-supported through 2027-12-31; PHP 8.4 through 2028-12-31; PHP 8.5 through 2029-12-31 (refresh before release). Woo Marketplace asks products to support PHP 7.4+ while the project's security-oriented minimum is 8.3; obtain written policy clarification rather than lowering the baseline without approval. |
| WordPress | **Target minimum 7.0**; test WordPress 7.0.8 and 7.1.4 | As of 2026-11-10, WordPress.org lists 7.1.4 as latest and 7.0.8 as the latest 7.0 patch. Woo's L-1 guidance says test the latest patch of the latest and immediately previous stable series. |
| WooCommerce | **Target minimum 11.1**; test WooCommerce 11.1.2 and 11.2.2 | As of 2026-11-10, Woo lists 11.2.2 as latest stable and 11.1.2 as the latest 11.1 patch. Woo's L-1 guidance applies to both WP and Woo for Woo-owned extensions; as a third-party add-on, WPEssential chooses and must test each version it claims. |
| Database/host | Recommended environment: HTTPS, MySQL 8.0+ or MariaDB 10.11+; do not advertise a Hub-specific database floor until schema tests support it | WordPress recommends MySQL 8.0+ or MariaDB 10.11+; WooCommerce documentation lists MySQL 8.0+ or MariaDB 10.6+ recommendations. Use the stricter WordPress-recommended environment for the test baseline. Validate custom table schema/indexes in Phase 7. |
| Order storage | HPOS is a required compatibility target; legacy post-based order storage tested while WooCommerce supports it | Use WooCommerce CRUD/query APIs only. Do not claim HPOS compatibility until both modes have been tested and the official compatibility declaration is accurate |
| React/admin JS | Use WordPress-provided packages and WordPress i18n APIs; bundle built assets locally | No CDN framework loading. Accessibility and admin interaction patterns are reviewed in Phase 2/7 |
| Distribution package | Free goes to WordPress.org; paid Pro goes to WooCommerce.com Marketplace | See `docs/DISTRIBUTION-PLAN.md`. Current Marketplace technical guidance and WooCommerce's announced PHP 8.1 floor create a PHP-policy issue to resolve with the vendor before submission |

## Translation behavior

- All PHP and React UI strings use the stable text domain and WordPress translation functions/packages. Include contextual and plural strings; translate notices, settings, labels, date/number display and accessibility labels.
- Internal resolution notes remain private. They must never be passed into customer-facing order notes or order emails by default.
- The admin interface follows the site's WordPress locale. Third-party multilingual plugin integrations and per-order language context are out of scope.
- Do not create translated order copies or duplicate case records per language. Case identity remains anchored to the WooCommerce order and exception reason.
- WPML and Polylang integrations are out of scope; the Hub must function without either product. Standard WordPress localization remains in scope.

## Release test matrix to refresh at Phase 7

The current compatibility matrix contains four WordPress/WooCommerce cells: WordPress 7.0.8 × WooCommerce 11.1.2; WordPress 7.0.8 × WooCommerce 11.2.2; WordPress 7.1.4 × WooCommerce 11.1.2; WordPress 7.1.4 × WooCommerce 11.2.2. Run each against PHP 8.3, the project's selected minimum; PHP 8.4/8.5 syntax and smoke checks remain a separate existing matrix. Include activation/bootstrap, Hub admin-menu registration, REST list route, schema install, and a clean empty-case response in the initial compatibility job. Add order fixtures, HPOS/legacy, upgrade/deactivation, and broader REST/security cases to their respective P7 gates rather than treating this first smoke as full certification. Refresh versions before release.

This matrix is a requirement, not a claim that the plugin has been tested. The first executable matrix job is being added under P7-02; passing it closes only the activation/bootstrap/inbox-list smoke scope described above.

## Current official references

- WordPress release archive: https://wordpress.org/download/releases/
- WooCommerce and WordPress L-1 policy: https://developer.woocommerce.com/docs/contribution/contributing/version-support-policy/
- WordPress server requirements / PHP recommendation: https://wordpress.org/about/requirements/
- WordPress 7.1 server compatibility: https://make.wordpress.org/hosting/handbook/server-environment/
- WooCommerce stable releases: https://developer.woocommerce.com/releases/
- WooCommerce server recommendations: https://woocommerce.com/document/update-php-wordpress/
- Upcoming PHP requirement for WooCommerce 11.6: https://developer.woocommerce.com/2026/09/29/php-8-1-requirement/
- PHP supported versions: https://www.php.net/supported-versions.php
- Woo Marketplace submission requirements: https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product
