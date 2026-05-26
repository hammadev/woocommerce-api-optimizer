=== API Optimizer for WooCommerce ===
Contributors: hammadanwar
Tags: woocommerce, rest-api, api, performance, field-filtering
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Your WooCommerce Store Deserves a Better API. GraphQL-like field filtering over REST — plus login and Stripe payments, out of the box.

== Description ==

**Your WooCommerce Store Deserves a Better API.**

Stop receiving 50+ fields when your app needs 3. ShopMobi gives your store GraphQL-like flexibility over REST — plus login & Stripe payments, out of the box.

Used by 000+ WooCommerce stores powering mobile apps & SPAs.

API Optimizer for WooCommerce is the backend engine for mobile and headless WooCommerce apps. Reduce payload size by requesting only the fields you need — via request header or query parameter — and ship custom endpoints your app actually uses.

= Field Filtering =

Works on products, orders, customers, and variations. No server-side changes required — clients control what they get:

**Via header (recommended for apps):**
`X-WC-Fields: id,name,price,images`

**Via query parameter:**
`?fields=id,name,price,images`

**Exclude specific fields:**
`X-WC-Except: meta_data,description` or `?except_fields=meta_data`

= Custom Endpoints =

* `POST /wp-json/wp/v2/users/login` — cookie-based login
* `POST /wp-json/wp/v2/users/register` — customer registration
* `POST /wp-json/wp/v2/users/update-profile` — update name and phone
* `POST /wp-json/wp/v2/users/reset-password/generate` — send 4-digit email code
* `POST /wp-json/wp/v2/users/reset-password/verify` — verify code and set new password
* `GET  /wp-json/wp/v2/general-settings` — country, currency, store location, active gateways
* `GET  /wp-json/wp/v2/payment-gateways` — active payment gateways
* `POST /wp-json/wp/v2/stripe-payment` — create Stripe PaymentIntent + EphemeralKey

= Third-Party Services =

This plugin connects to **Stripe** (https://stripe.com) for payment processing when the `/stripe-payment` endpoint is called. Stripe's privacy policy is at https://stripe.com/privacy and terms at https://stripe.com/legal. Your Stripe API keys are stored in the WordPress database and sent only to Stripe's servers. No data is sent to the plugin author.

= Product Variations =

Variation responses are enriched automatically — raw variation IDs are replaced with full objects including attributes, pricing, stock, and image URL.

== Installation ==

1. Download the plugin zip.
2. Upload to **Plugins > Add New > Upload Plugin** and activate.
3. Run `composer install` inside the plugin folder (or use a pre-built release that includes the `vendor/` directory).
4. Navigate to **WooCommerce > API Optimizer** to enter your Stripe API keys.

= Composer (manual install) =

`composer install --no-dev --optimize-autoloader`

== Frequently Asked Questions ==

= Does field filtering affect performance? =

The full WooCommerce response is still built internally; fields are stripped before the response is sent. The main benefit is reduced network payload size for mobile/headless clients.

= Can I use both header and query param at the same time? =

The header takes priority. If `X-WC-Fields` is present, the `fields` query param is ignored.

= Does this work with the WooCommerce Blocks REST API? =

Currently only the classic WooCommerce REST API (`/wc/v3/`) is supported.

= Is Stripe required? =

No. The Stripe endpoint only activates when you provide API keys under WooCommerce > API Optimizer. All other features work without Stripe.

== Screenshots ==

1. Settings page under WooCommerce > API Optimizer.
2. Field-filtered product response — only requested fields returned.

== Changelog ==

= 1.0.0 =
* Initial release.
* Field filtering via header (`X-WC-Fields`, `X-WC-Except`) and query params.
* Auth, password reset, general settings, Stripe payment, and payment gateway endpoints.
* Variation response enhancer.
* Stripe keys stored securely in WP options.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
