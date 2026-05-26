=== ShopMobi – API Optimizer for WooCommerce ===
Contributors: hammadev2
Tags: woocommerce, rest-api, api, performance, mobile
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ShopMobi reduces WooCommerce REST API response size with field filtering and adds ready-made endpoints for auth, Stripe payments, and store settings.

== Description ==

Stop receiving 50+ fields when your app needs 3. ShopMobi – API Optimizer for WooCommerce gives your store GraphQL-like flexibility over REST — plus login, password reset, and Stripe payments out of the box.

Built for developers creating mobile apps, Flutter apps, React Native apps, or any headless WooCommerce frontend.

= Field Filtering =

Apply field filtering to any WooCommerce REST API response. No changes required on the server side — clients request only what they need.

**Via request header (recommended for apps):**
`X-WC-Fields: id,name,price,images`

**Via query parameter:**
`?fields=id,name,price,images`

**Exclude specific fields:**
`X-WC-Except: meta_data,description` or `?except_fields=meta_data`

When both header and query parameter are provided, the header takes priority. The plugin applies filtering after WooCommerce builds the full response, so all existing WooCommerce hooks and permissions remain intact.

= Custom REST Endpoints =

The plugin registers the following custom endpoints under the `shopmobi/v1` namespace:

**Authentication**

* `POST /wp-json/shopmobi/v1/users/login` — Login with username and password
* `POST /wp-json/shopmobi/v1/users/register` — Register a new customer account
* `POST /wp-json/shopmobi/v1/users/update-profile` — Update first name, last name, and phone (requires login)

**Password Reset**

* `POST /wp-json/shopmobi/v1/users/reset-password/generate` — Send a 4-digit reset code to the user's email
* `POST /wp-json/shopmobi/v1/users/reset-password/verify` — Verify the reset code and set a new password

**Store Information**

* `GET /wp-json/shopmobi/v1/general-settings` — Country, currency, store location, and active payment gateways
* `GET /wp-json/shopmobi/v1/payment-gateways` — Available payment gateways

**Payments**

* `POST /wp-json/shopmobi/v1/stripe-payment` — Create a Stripe PaymentIntent and EphemeralKey for mobile checkout (requires login)

= Product Variation Enhancement =

Variation responses are enriched automatically. Raw variation IDs in product responses are replaced with full objects including:

* Pricing (regular price, sale price, on_sale flag)
* SKU and stock quantity
* Stock status
* Attributes with labels and slugs
* Variation image URL

= Third-Party Services =

This plugin optionally integrates with **Stripe** (https://stripe.com) for payment processing. The Stripe integration is inactive until you provide API keys under **WooCommerce > API Optimizer**.

When the `/shopmobi/v1/stripe-payment` endpoint is called, payment data (amount, currency, Stripe customer ID) is sent directly to Stripe's servers. No payment data passes through ShopMobi or any other third party.

* Stripe Privacy Policy: https://stripe.com/privacy
* Stripe Terms of Service: https://stripe.com/legal

Your Stripe API keys are stored in your WordPress database and are never shared with the plugin author.

== Installation ==

= Standard Installation (Recommended) =

1. Go to **Plugins > Add New > Upload Plugin** in your WordPress admin.
2. Upload the plugin zip file and click **Install Now**.
3. Click **Activate Plugin**.
4. Optionally, go to **WooCommerce > API Optimizer** to enter your Stripe API keys.

= Installation from Source =

1. Clone the repository and navigate to the plugin folder.
2. Run: `composer install --no-dev --optimize-autoloader`
3. Copy the folder to `wp-content/plugins/` and activate from the Plugins screen.

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 6.0 or higher
* PHP 7.4 or higher

== Frequently Asked Questions ==

= Does field filtering affect server performance? =

The full WooCommerce response is built internally before filtering is applied, so server processing time is unchanged. The benefit is a smaller network payload for mobile and headless clients.

= Does this replace the WooCommerce REST API? =

No. This plugin adds a filtering layer on top of the standard WooCommerce REST API. All existing WooCommerce authentication, permissions, and hooks still apply.

= Can I use both a header and a query parameter at the same time? =

The header takes priority. If `X-WC-Fields` is present, the `fields` query parameter is ignored for that request.

= Which WooCommerce endpoints support field filtering? =

* `/wc/v3/products` and individual product responses
* `/wc/v3/products/{id}/variations`
* `/wc/v3/orders` and individual order responses
* `/wc/v3/customers` and individual customer responses

= Is Stripe required? =

No. Stripe is completely optional. All other features — field filtering, auth endpoints, general settings — work without any Stripe configuration.

= Does this work with WooCommerce HPOS (High-Performance Order Storage)? =

Yes. The plugin filters WooCommerce REST API responses and does not make direct database queries, so it is fully compatible with HPOS.

= Is the plugin compatible with caching plugins? =

The field filtering works on REST API responses. If your caching plugin caches REST API responses, those cached responses will not be filtered. Disable REST API caching or configure it to vary by the `X-WC-Fields` header.

= Where are Stripe API keys stored? =

Stripe API keys are stored in the WordPress options table via the standard WordPress Settings API. They are never logged, exposed in source code, or transmitted to any party other than Stripe.

= Does the plugin collect any data? =

The plugin stores the following data in your own WordPress database:

* Stripe customer IDs in user meta (`stripe_cust_id`) — only when Stripe is used
* Temporary password reset codes and expiry timestamps in user meta — deleted immediately after use

No data is sent to ShopMobi or any external service other than Stripe (when explicitly used).

= Can I use this plugin without WooCommerce? =

No. WooCommerce must be installed and active. If WooCommerce is not detected, the plugin will show an admin notice and will not register any hooks or endpoints.

== Screenshots ==

1. Settings page under WooCommerce > API Optimizer — enter optional Stripe API keys.
2. Example: field-filtered product list response returning only `id`, `name`, and `price`.
3. Example: variation response enriched with full attributes, pricing, and image URL.

== Privacy Policy ==

This plugin stores data in your own WordPress database only:

* **Stripe Customer IDs** (`stripe_cust_id` user meta) — created when a user makes a Stripe payment. Stored locally and shared only with Stripe to identify returning customers.
* **Password Reset Codes** — a temporary 4-digit code and expiry timestamp stored in user meta. Both are deleted immediately after a successful password reset.

This plugin does not track users, send analytics, or transmit any personal data to ShopMobi or any third party, except for payment data sent directly to Stripe when the Stripe endpoint is used.

For Stripe's data handling practices, see https://stripe.com/privacy.

== Changelog ==

= 1.0.0 =
* Initial release.
* Field filtering via `X-WC-Fields` / `X-WC-Except` headers and `fields` / `except_fields` query parameters.
* Applies to products, orders, customers, and variation endpoints.
* Auth endpoints: login, register, update profile.
* Password reset endpoints: generate and verify.
* General settings endpoint: country, currency, store location, active gateways.
* Payment gateways endpoint.
* Stripe PaymentIntent endpoint with EphemeralKey support.
* Product variation response enhancer.
* Admin settings page under WooCommerce > API Optimizer.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
