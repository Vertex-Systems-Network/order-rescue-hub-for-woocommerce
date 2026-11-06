# Distribution and submission plan

**Status:** Planned; no application, package, or submission has been made.  
**Research checked:** 2026-11-06. Recheck official rules at packaging and before each submission because review criteria can change.

## Release channels

| Edition | Destination | Intended listing |
|---|---|---|
| Free | WordPress.org Plugin Directory | Standalone useful feature set with the exception queue, filters, manual notes and basic resolution history |
| Pro (planned $99/year) | WooCommerce.com Marketplace | Paid extension/package with alerts, workflows, assignments, SLA and advanced reporting |

Use one canonical product specification and option bank. Separate distribution packaging and licensing concerns from feature logic. Do not assume review approval or a guaranteed submission date.

## WordPress.org — Free edition

Before submission:

- Finalize a GPL-compatible license. Current directory guidance requires GPL v2 or later compatibility.
- Ensure the Free edition provides real, documented functionality on its own.
- Prepare a complete installable production ZIP and directory readme/assets. Remove development-only artifacts, placeholders, uncompiled source-only UI and unnecessary files; the package must be ready for end users.
- Audit the complete plugin against current detailed directory guidelines, security practices, privacy/data handling, trademark rules and external-service disclosures.
- Confirm the chosen slug/name, developer/support contact and update ownership.
- Test activation, upgrade, deactivation, uninstall behavior, supported WordPress/PHP/WooCommerce combinations, HPOS and multilingual scenarios.
- Submit through the official Plugin Directory process. If accepted, use the assigned SVN repository for directory releases and follow current readme/release requirements.
- Keep upgrade prompts restrained and compliant; do not make the Free plugin a non-functional shell for paid software.

**Evidence required to mark ready:** frozen release ZIP, checksum, readme/assets, license review, security/privacy checklist, tested version matrix, reproducible release notes, and a clean install/update verification report.

## WooCommerce.com Marketplace — paid Pro edition

Before application/submission:

- Apply for and receive approval for a Woo Marketplace vendor/partner account.
- Prepare the business rationale, product differentiation, company/developer history, customer support plan and requested product/demo/test information.
- Ask the Marketplace team to confirm the listing model for this split distribution: paid standalone Pro extension that depends on the WordPress.org Free base, or a Marketplace freemium listing. The current public guidance describes paid and freemium options; it does not by itself settle which packaging is best for this exact two-channel design.
- Confirm the Pro billing/licensing approach, marketplace revenue share, renewal/update flow and pricing parity. Keep $99/year provisional until confirmed.
- Use **WPEssential Order Rescue Hub** as the distinctive brand and “for WooCommerce” only as a truthful compatibility descriptor; review all listing copy, logo and slug against current Woo trademark rules and Marketplace naming guidance.
- Support the two latest major WordPress and WooCommerce releases at submission. The project baseline targets PHP 8.3+ because WordPress/WooCommerce recommend it and PHP 8.1 is already EOL. WooCommerce 11.6 is announced to require PHP 8.1+ from January 2027. The Marketplace submission page still says extensions must support PHP 7.4+ while recommending PHP 8.3+, so this published policy conflicts with the changing platform floor. Obtain written Marketplace clarification before submission; submit the PHP 8.3+ baseline only if accepted. Do not recommend an EOL runtime to merchants.
- Meet WordPress accessibility coding standards and Woo UX/security/development guidelines; use WooCommerce APIs and avoid adding telemetry or intrusive admin ads.
- Prepare test instructions, documentation, demo/store and critical-flow evidence.
- Run the currently required QIT suites: API, end-to-end, activation, security, PHPCompatibility, malware and validation. Record exact versions and results. Recheck the required suite at submission time.
- Submit the product, address review findings, and wait for verifiable approval/listing before calling the track complete.

**Evidence required to mark ready:** vendor approval, confirmed listing/billing model, production package, support/docs/demo bundle, trademark and licensing review, compatibility matrix, accessibility review, QIT results, and reviewer feedback closure.

## Shared release gates

1. **One source of truth:** no edition-specific duplicate settings; Free/Pro differences map to canonical option keys in README.
2. **License and notices:** select the project license and ensure bundled assets/dependencies are compatible with both channels.
3. **Dependency behavior:** Pro must clearly identify whether the Free base is required; fail gracefully and explain missing dependencies.
4. **Data migration:** define schema upgrades, rollback expectations, uninstall behavior, retention and export/delete responsibilities.
5. **Security:** capabilities, nonces, input validation, escaping, privacy minimization, redacted logs and explicit approval for financially consequential actions.
6. **Compatibility:** Use the baseline in `docs/PLATFORM-BASELINE.md`; recheck the latest two stable WP/Woo majors, PHP lifecycle and Marketplace policy before release. Test HPOS, classic storage where supported, WPML and Polylang; advertise only verified combinations.
7. **Packaging:** separate clean production artifacts from source/docs/test tooling. No CLI-related task or user-facing CLI work before the plugin is final.
8. **Pricing/support:** finalize price parity, license activation/renewal, update policy, support channel and service expectations.
9. **Review closure:** track WordPress.org and Woo Marketplace review comments separately; do not conflate submission with approval or publication.

## Official references

- WordPress.org directory overview and license requirements: https://developer.wordpress.org/plugins/wordpress-org/
- WordPress.org submission and SVN publishing flow: https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/
- WordPress.org detailed plugin guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- Woo Marketplace submission checklist: https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product
- Woo Marketplace vendor process: https://developer.woocommerce.com/docs/woo-marketplace/getting-started/
- Woo Marketplace paid/freemium monetization: https://developer.woocommerce.com/docs/woo-marketplace/monetization-expectations
- Woo trademark guidelines: https://woocommerce.com/trademark-guidelines/
- Woo Marketplace product naming style: https://developer.woocommerce.com/docs/woo-marketplace/content-style-guide
