=== API Optimizer for WooCommerce ===
Contributors: hammadev2
Tags: woocommerce, rest-api, api, performance, mobile
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Reduce WooCommerce REST API response size by requesting only the fields you need — plus ready-made endpoints for auth, Stripe payments, and store settings.

== Description ==

Stop receiving 50+ fields when your app needs 3. API Optimizer for WooCommerce gives your store GraphQL-like flexibility over REST — plus login, password reset, and Stripe payments out of the box.

Built for developers building mobile apps, Flutter apps, React Native apps, or any headless WooCommerce frontend.

= Field Filtering =

Works on products, orders, customers, and variations. No server-side changes required — clients control what they receive:

**Via request header (recommended for apps):**
`X-WC-Fields: id,name,price,images`

**Via query parameter:**
`?fields=id,name,price,images`

**Exclude specific fields:**
`X-WC-Except: meta_data,description` or `?except_fields=meta_data`

Header takes priority when both are provided. The plugin applies filtering after WooCommerce builds the full response, so all existing WooCommerce hooks and permissions still apply.

= Custom REST Endpoints =

The plugin registers the following custom endpoints under the `shopmobi/v1` namespace:

**Authentication**

* `POST /wp-json/shopmobi/v1/users/login` — Login with username and password, returns user data and meta
* `POST /wp-json/shopmobi/v1/users/register` — Register a new customer account
* `POST /wp-json/shopmobi/v1/users/update-profile` — Update first name, last name, and phone number

**Password Reset**

* `POST /wp-json/shopmobi/v1/users/reset-password/generate` — Send a 4-digit reset code to the user's email
* `POST /wp-json/shopmobi/v1/users/reset-password/verify` — Verify the code and set a new password

**Store Information**

* `GET /wp-json/shopmobi/v1/general-settings` — Returns country, currency, store location, and active payment gateways
* `GET /wp-json/shopmobi/v1/payment-gateways` — Returns available payment gateways

**Payments**

* `POST /wp-json/shopmobi/v1/stripe-payment` — Creates a Stripe PaymentIntent and EphemeralKey for mobile checkout

= Product Variation Enhancement =

Variation responses are enriched automatically. Raw variation IDs in product responses are replaced with full variation objects including:

* Pricing (regular, sale, on_sale flag)
* SKU and stock quantity
* Stock status
* Attributes with labels and slugs
* Variation image URL

= Third-Party Services =

This plugin integrates with **Stripe** (https://stripe.com) for payment processing. The Stripe integration is entirely optional — it is only active when you provide Stripe API keys under **WooCommerce > API Optimizer**.

When the `/stripe-payment` endpoint is called, the plugin sends payment data (amount, currency, customer ID) directly to Stripe's servers. No payment data passes through ShopMobi or any other third party.

* Stripe Privacy Policy: https://stripe.com/privacy
* Stripe Terms of Service: https://stripe.com/legal

Your Stripe API keys are stored in your WordPress database and are never shared with the plugin author.

== Installation ==

= Standard Installation (Recommended) =

Download the latest release zip from the plugin page or GitHub releases. The release zip includes all dependencies pre-bundled.

1. Go to **Plugins > Add New > Upload Plugin** in your WordPress admin.
2. Upload the zip file and click **Install Now**.
3. Click **Activate Plugin**.
4. Go to **WooCommerce > API Optimizer** to enter your Stripe API keys (optional).

= Installation from Source =

If you clone or download the source code directly, you must run Composer to install dependencies before activating:

1. Navigate to the plugin folder in your terminal.
2. Run: `composer install --no-dev --optimize-autoloader`
3. Upload the folder to `wp-content/plugins/` and activate.

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 6.0 or higher
* PHP 7.4 or higher

== Frequently Asked Questions ==

= Does field filtering affect server performance? =

The full WooCommerce response is built internally before filtering is applied, so server processing time is unchanged. The primary benefit is reduced network payload — useful for mobile apps and slow connections.

= Does this replace the WooCommerce REST API? =

No. This plugin adds a filtering layer on top of the existing WooCommerce REST API. All existing WooCommerce authentication, permissions, and hooks still apply.

= Can I use both a header and a query parameter at the same time? =

The header takes priority. If `X-WC-Fields` is present, the `fields` query parameter is ignored for that request.

= Which endpoints support field filtering? =

Field filtering works on:
* `/wc/v3/products` and individual product responses
* `/wc/v3/products/{id}/variations`
* `/wc/v3/orders` and individual order responses
* `/wc/v3/customers` and individual customer responses

= Is Stripe required to use this plugin? =

No. Stripe is completely optional. The Stripe endpoint only activates when you enter API keys under **WooCommerce > API Optimizer**. All other features — field filtering, auth endpoints, general settings — work without any Stripe configuration.

= Does this work with WooCommerce HPOS (High-Performance Order Storage)? =

The plugin filters WooCommerce REST API responses which are compatible with HPOS. Direct database queries are not used.

= Is the plugin compatible with caching plugins? =

The field filtering works on REST API responses. If your caching plugin caches REST API responses, cached responses will not be filtered. REST API caching should be disabled or configured to vary by the `X-WC-Fields` header.

= Where are Stripe API keys stored? =

Stripe API keys are stored in the WordPress options table using the standard WordPress Settings API. They are never logged, exposed in source code, or transmitted to any party other than Stripe.

= Does the plugin collect any data? =

The plugin stores the following data in your WordPress database:

* Stripe customer IDs in user meta (key: `stripe_cust_id`) — only when Stripe is used
* Temporary password reset codes and expiry timestamps in user meta — deleted immediately after use

No data is sent to ShopMobi or any analytics service.

= Can I use this plugin without WooCommerce? =

No. WooCommerce must be installed and active. The plugin will display an admin notice and deactivate its hooks if WooCommerce is not detected.

== Screenshots ==

1. Settings page under WooCommerce > API Optimizer — enter Stripe API keys.
2. Example: field-filtered product list response returning only `id`, `name`, and `price`.
3. Example: variation response enriched with full attributes, pricing, and image URL.

== Privacy Policy ==

This plugin stores data in your own WordPress database only:

* **Stripe Customer IDs** (`stripe_cust_id` user meta) — created when a user completes a Stripe payment. Stored locally, shared only with Stripe to identify returning customers.
* **Password Reset Codes** — a temporary 4-digit code and expiry timestamp stored in user meta. Both values are deleted immediately after the password reset is verified.

This plugin does not track users, send analytics, or transmit any personal data to ShopMobi or any third party, with the exception of payment data sent directly to Stripe when the Stripe endpoint is used.

For Stripe's data handling practices, see https://stripe.com/privacy.

== Changelog ==

= 1.0.0 =
* Initial release.
* Field filtering via `X-WC-Fields` / `X-WC-Except` headers and `fields` / `except_fields` query parameters.
* Applies to products, orders, customers, and variation endpoints.
* Auth endpoints: login, register, update profile.
* Password reset endpoints: generate and verify code.
* General settings endpoint: country, currency, store location, active gateways.
* Payment gateways endpoint.
* Stripe PaymentIntent endpoint with EphemeralKey support.
* Product variation response enhancer.
* Admin settings page under WooCommerce > API Optimizer for Stripe key management.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
