=== WPEssential Order Rescue Hub ===
Contributors: vertexsystemsnetwork
Tags: woocommerce, orders, order-management, admin
Requires at least: 7.0
Requires PHP: 8.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

A focused workspace for finding, prioritizing, and resolving WooCommerce order exceptions.

== Description ==

Order Rescue Hub brings common order exceptions into one WooCommerce admin workspace. The initial Free edition detects failed or aging pending payments, incomplete shipping details, selected stock issues, and processing delays. Staff can filter the queue, review the order context, add private notes, and record a case outcome.

The Hub keeps its case state separate from WooCommerce order status. Detection does not capture payments, issue refunds, cancel orders, change order status, send customer messages, or grant order access through case assignment.

This is an early development release. Compatibility and marketplace submission claims are not yet verified.

== Installation ==

1. Install and activate WooCommerce.
2. Upload and activate the WPEssential Order Rescue Hub plugin.
3. Open WooCommerce > Order Rescue Hub to review exceptions.

== Frequently Asked Questions ==

= Does this plugin change an order or payment automatically? =

No. The Free edition records and organizes exceptions and staff decisions. It does not execute payment capture, refunds, cancellations, or payment-related order status changes.

= Are resolution notes sent to the customer? =

No. Notes added in Order Rescue Hub are private internal case notes.

= Does assigning a case grant a user permission to view an order? =

No. Order access continues to use the site's existing WooCommerce and WordPress capabilities.

= Does the plugin work without WooCommerce? =

WooCommerce is required. The plugin shows an administrator notice and pauses monitoring if WooCommerce is unavailable.

== Changelog ==

= 0.1.0 =
* Initial Free exception inbox, reason filters, age filters, private notes, case history, and manual case outcomes.
* Added HPOS-safe order access through WooCommerce CRUD and query APIs.
