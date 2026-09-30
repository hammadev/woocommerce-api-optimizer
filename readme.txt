=== ShopMobi – API Optimizer for WooCommerce ===
Contributors: hammadev2
Tags: woocommerce, rest-api, headless, mobile-app, performance
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Is your WooCommerce API slowing your app? Cut REST payloads up to 90% with field filtering, plus ready-made auth, password reset & Stripe endpoints.

== Description ==

Building an SPA or mobile app on WooCommerce? Stop receiving 50+ fields when your screen needs 3 — and stop stitching together a JWT plugin, a filtering snippet, and a Stripe integration to get there.

ShopMobi is an all-in-one WooCommerce REST API layer for developers building **single-page apps, Flutter apps, React Native apps, or any headless WooCommerce frontend**. It ships two things:

1. **Field Filtering** — trim the responses of the *existing* WooCommerce `wc/v3` endpoints (products, variations, orders, customers). No new routes: you keep calling the standard WooCommerce REST API and opt into smaller responses.
2. **Custom Endpoints** — ready-made routes under `shopmobi/v1` for login, registration, password reset, store settings, and Stripe payments.

📖 **Full API reference with every request, response and error code:** [GitHub documentation](https://github.com/hammadev/woocommerce-api-optimizer#readme)

= At a Glance =

Same endpoint, same credentials, one query parameter added:

    GET /wp-json/wc/v3/products/101?fields=id,name,price

* **Fields returned per product:** 66 by default → only the ones you ask for
* **Payload for one product:** ~2.8 KB → ~130 B
* **Payload for a 20-product listing screen:** ~56 KB → ~2.5 KB
* **New endpoints to learn:** none — it's the same `wc/v3` route

= Why ShopMobi? =

* **One plugin instead of three.** A headless WooCommerce frontend usually needs a JWT/auth plugin, hand-written response-filtering code, and a separate Stripe integration. ShopMobi ships all of it as one tested package.
* **Built for SPA and mobile clients.** Flutter, React Native, and native apps pay for every extra byte in load time, memory, and mobile data. A product screen that needs 4 fields shouldn't download 60+.
* **GraphQL-like control without leaving REST.** Pick exactly the fields you want, per request — no schema, no query language, no extra server.
* **Auth, password reset, and Stripe included.** Login, registration, profile updates, password reset, and Stripe `PaymentIntent` / `EphemeralKey` creation are ready to use, so you don't build and secure them yourself.

= Field Filtering =

Add an **include list** or an **exclude list** to any supported `wc/v3` request, as a header or a query parameter:

* **Include only these fields:** header `X-WC-Fields: id,name,price` or query `?fields=id,name,price`
* **Exclude these fields:** header `X-WC-Except: meta_data` or query `?except_fields=meta_data`

How it behaves:

* WooCommerce builds its normal response first — every hook, permission check, and sanitization rule still runs. ShopMobi only trims the **top-level keys** right before the response is sent.
* Collections (e.g. a product list) are trimmed item by item, so every item comes back in the same shape.
* If a header and a query parameter are both sent, the **header wins**. If an include and an exclude list are both sent, both apply.
* With an include list, only the listed fields come back — add `id` yourself if your app needs it.

Example — only the fields a product list screen needs:

    curl "https://example.com/wp-json/wc/v3/products?fields=id,name,price,images" \
      -u consumer_key:consumer_secret

Response (every product in the list is trimmed the same way):

    [
      {
        "id": 101,
        "name": "Classic T-Shirt",
        "price": "24.00",
        "images": [{ "id": 55, "src": "https://example.com/wp-content/uploads/tshirt.jpg" }]
      }
    ]

Example — drop heavy fields from orders instead:

    curl "https://example.com/wp-json/wc/v3/orders?except_fields=meta_data,_links,tax_lines" \
      -u consumer_key:consumer_secret

**Supported endpoints:** products, product variations, orders, order refunds, and customers (list and single-item routes).

**Variation enhancement:** product responses return full variation objects instead of raw variation IDs — pricing, sale status, SKU, stock, attributes, and image URL — so a product page doesn't need one extra request per variation.

= Custom Endpoints =

All routes live under `https://your-site.com/wp-json/shopmobi/v1`:

* `POST /users/login` — log in with username and password (cookie-based, rate limited)
* `POST /users/register` — create a customer account (rate limited)
* `POST /users/update-profile` — update name and phone *(requires login)*
* `POST /users/reset-password/generate` — email a password reset key
* `POST /users/reset-password/verify` — verify the key and set a new password
* `GET /general-settings` — store country, currency format, and active payment gateways
* `GET /store-location` — store address *(requires login)*
* `GET /payment-gateways` — active payment gateways
* `POST /stripe-payment` — create a Stripe `PaymentIntent` + `EphemeralKey` for Stripe's mobile `PaymentSheet` *(requires login)*

Endpoints marked *requires login* need a valid WordPress session: cookie authentication with an `X-WP-Nonce` header, or [Application Passwords](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/) over HTTP Basic Auth. `/users/login` sets standard WordPress auth cookies; it does not return a bearer token.

Request bodies, response examples, and error codes for every endpoint are in the [GitHub documentation](https://github.com/hammadev/woocommerce-api-optimizer#custom-endpoints).

= Third-Party Services =

This plugin optionally integrates with **Stripe** (https://stripe.com) for payment processing. The Stripe integration is inactive until you provide API keys under **WooCommerce > API Optimizer**.

When the `/shopmobi/v1/stripe-payment` endpoint is called, payment data (amount, currency, Stripe customer ID) is sent directly to Stripe's servers. No payment data passes through ShopMobi or any other third party.

* Stripe Privacy Policy: https://stripe.com/privacy
* Stripe Terms of Service: https://stripe.com/legal

Your Stripe API keys are stored in your WordPress database and are never shared with the plugin author.

= Privacy =

This plugin stores data in your own WordPress database only:

* **Stripe customer IDs** (`stripe_cust_id` user meta) — created when a user makes a Stripe payment, and shared only with Stripe to identify returning customers.
* **Password reset keys** — handled entirely by WordPress core (`get_password_reset_key()` / `reset_password()`).

The plugin does not track users, send analytics, or transmit personal data to ShopMobi or any third party, except payment data sent directly to Stripe when the Stripe endpoint is used.

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

= Where is the full API documentation? =

Every endpoint, with request bodies, response examples, and error codes, is documented on [GitHub](https://github.com/hammadev/woocommerce-api-optimizer#readme).

= Does field filtering affect server performance? =

The full WooCommerce response is built internally before filtering is applied, so server processing time is unchanged. The benefit is a smaller network payload for mobile and headless clients.

= Does this replace the WooCommerce REST API? =

No. This plugin adds a filtering layer on top of the standard WooCommerce REST API, and adds separate custom endpoints under `shopmobi/v1` alongside it. All existing WooCommerce authentication, permissions, and hooks still apply.

= What happens if I stop using field filtering — is there anything to migrate? =

Nothing. Filtering only runs when a request includes `fields` / `X-WC-Fields` or `except_fields` / `X-WC-Except`. Stop sending those and every endpoint returns WooCommerce's normal, full response.

= Can I use both a header and a query parameter at the same time? =

The header takes priority. If `X-WC-Fields` is present, the `fields` query parameter is ignored for that request — same rule for `X-WC-Except` vs `except_fields`.

= Is Stripe required? =

No. Stripe is completely optional. All other features — field filtering, auth endpoints, general settings — work without any Stripe configuration.

= Does this work with WooCommerce HPOS (High-Performance Order Storage)? =

Yes. The plugin filters WooCommerce REST API responses and does not make direct database queries, so it is fully compatible with HPOS.

= Is the plugin compatible with caching plugins? =

The field filtering works on REST API responses. If your caching plugin caches REST API responses, those cached responses will not be filtered. Disable REST API caching or configure it to vary by the `X-WC-Fields` / `X-WC-Except` headers.

= Where are Stripe API keys stored? =

Stripe API keys are stored in the WordPress options table via the standard WordPress Settings API (`shopmobi_ao_stripe_secret_key`, `shopmobi_ao_stripe_public_key`). They are never logged, exposed in source code, or transmitted to any party other than Stripe.

= Does the plugin collect any data? =

The plugin stores the following data in your own WordPress database:

* Stripe customer IDs in user meta (`stripe_cust_id`) — only when Stripe is used
* Temporary password reset keys — handled entirely by WordPress core and invalidated automatically after use

No data is sent to ShopMobi or any external service other than Stripe (when explicitly used).

= Can I use this plugin without WooCommerce? =

No. WooCommerce must be installed and active. If WooCommerce is not detected, the plugin will show an admin notice and will not register any hooks or endpoints.

== Screenshots ==

1. Settings page under WooCommerce > API Optimizer — enter optional Stripe API keys.
2. Example: field-filtered product list response returning only `id`, `name`, and `price`.
3. Example: variation response enriched with full attributes, pricing, and image URL.

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
