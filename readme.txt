=== ShopMobi – API Optimizer for WooCommerce ===
Contributors: hammadev2
Tags: woocommerce, rest-api, spa, headless, mobile-app, api, performance, mobile
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The all-in-one WooCommerce REST API layer for SPA and mobile app developers: field filtering plus ready-made auth, password reset, store settings, and Stripe payment endpoints.

== Description ==

Building an SPA or mobile app on WooCommerce? Stop receiving 50+ fields when your screen needs 3 — and stop stitching together a JWT plugin, a filtering snippet, and a Stripe integration to get there. ShopMobi is a full-stop, all-in-one WooCommerce REST API layer built for developers creating **single-page apps, Flutter apps, React Native apps, or any headless WooCommerce frontend**.

This plugin ships two things, each fully documented in its own section below:

1. **Field Filtering** — a response-shaping layer on top of the *existing* WooCommerce `wc/v3` endpoints (products, variations, orders, customers). No new routes — you keep calling the standard WooCommerce REST API and opt into smaller responses. See **Field Filtering** below.
2. **Custom Endpoints** — new routes under the `shopmobi/v1` namespace for auth, password reset, store settings, and Stripe payments. See **Custom Endpoints** below.

= At a Glance =

`GET /wp-json/wc/v3/products/101?fields=id,name,price` — same endpoint, same credentials, one query param added.

| | Default WooCommerce | With ShopMobi |
|---|---|---|
| Fields returned per product | 66 | exactly what you asked for |
| Payload, one product | ~2.8 KB | ~130 B (**-95%**) |
| New endpoints to learn | — | none — same `wc/v3` route |

Full breakdown with real JSON in **Field Filtering** below.

= Why ShopMobi? =

Every field WooCommerce's REST API returns is a field your app has to receive, parse, store, and — on a mobile connection — pay for in bytes and battery. ShopMobi removes that tax without asking you to change how you talk to WooCommerce:

* **One plugin instead of three.** A headless WooCommerce frontend usually needs a JWT/auth plugin, hand-written response-filtering code, and a separate Stripe integration, bolted together and kept in sync. ShopMobi ships all of it as one tested package.
* **Purpose-built for SPA and mobile clients.** Flutter, React Native, and native apps pay for every extra byte in load time, memory, and mobile data. A product screen that needs 4 fields shouldn't ship 60+.
* **GraphQL-like control without leaving REST.** Pick exactly the fields you want, per request — no schema, no query language, no separate server to run.
* **Auth, password reset, and Stripe payments included.** Login, registration, profile updates, password reset, and Stripe `PaymentIntent`/`EphemeralKey` creation ship ready-made, so you don't build and secure them yourself.

More on how it works and what it costs to adopt: **Field Filtering** below and the **Frequently Asked Questions** section.

= Third-Party Services =

This plugin optionally integrates with **Stripe** (https://stripe.com) for payment processing. The Stripe integration is inactive until you provide API keys under **WooCommerce > API Optimizer**.

When the `/shopmobi/v1/stripe-payment` endpoint is called, payment data (amount, currency, Stripe customer ID) is sent directly to Stripe's servers. No payment data passes through ShopMobi or any other third party.

* Stripe Privacy Policy: https://stripe.com/privacy
* Stripe Terms of Service: https://stripe.com/legal

Your Stripe API keys are stored in your WordPress database and are never shared with the plugin author.

== Field Filtering ==

Field filtering applies **on top of** the standard WooCommerce REST API — there are no new routes. You call the same `wc/v3` endpoints you already use, authenticated the same way (consumer key/secret or OAuth1.0a), and opt into a smaller response per request. This is the layer that lets your SPA or mobile app request exactly the shape it renders, instead of parsing and discarding the rest.

= Quick Reference =

| Method | Include fields | Exclude fields |
|--------|----------------|----------------|
| Header | `X-WC-Fields: id,name,price` | `X-WC-Except: meta_data` |
| Query param | `?fields=id,name,price` | `?except_fields=meta_data` |

Header takes priority when both are present.

= How It Works =

1. Your app calls a standard `wc/v3` endpoint exactly as it does today — same URL, same auth.
2. You add either an **include list** (`fields` / `X-WC-Fields`) naming the fields you want, or an **exclude list** (`except_fields` / `X-WC-Except`) naming the fields you don't. Both are comma-separated field names.
3. WooCommerce builds its normal, full response object first — every hook, capability check, and sanitization rule runs exactly as it would without ShopMobi installed.
4. Right before the response is sent, ShopMobi reads your list and keeps (or drops) the matching **top-level keys** of the response — e.g. `id`, `name`, `price`, `images`. Nested data such as `images` or `variations` comes back as WooCommerce built it when you request that field; ShopMobi doesn't reach inside it.
5. This runs per resource, so a collection endpoint (a product list) is filtered the same way as a single-resource endpoint (one product) — every item in the list comes back with the same trimmed shape.
6. If both an include and an exclude parameter are sent, **both apply**: the response is trimmed to the include list first, then any excluded keys are removed from what's left — so an excluded field never comes back even if it was also in the include list. If both a header and a query parameter are sent for the same direction, the **header wins**.
7. If you use `fields` / `X-WC-Fields`, only the fields you list are returned — remember to include `id` explicitly if your client needs it, it isn't added automatically.

The endpoint, the auth, and the field names on the wire are identical either way — you're just choosing how many of them come back.

= Product Variation Enhancement =

Variation responses are enriched automatically. Raw variation IDs in product responses are replaced with full objects including:

* Pricing (regular price, sale price, on_sale flag)
* SKU and stock quantity
* Stock status
* Attributes with labels and slugs
* Variation image URL

This runs before field filtering, so requesting the `variations` field returns the enhanced objects, not raw IDs — the shape a product page actually renders, not a list of IDs that costs one extra request each to resolve. See it in context in Example 2 below.

= With vs. Without ShopMobi =

Same request, same endpoint, same credentials — `GET /wp-json/wc/v3/products/101`. The only difference is one query string.

**Without ShopMobi** (standard WooCommerce, unfiltered — abridged here, the real response has 66 top-level fields):

```json
{
  "id": 101,
  "name": "Classic T-Shirt",
  "slug": "classic-t-shirt",
  "permalink": "https://example.com/product/classic-t-shirt/",
  "date_created": "2025-01-14T10:22:03",
  "type": "variable",
  "status": "publish",
  "description": "<p>A soft, breathable cotton t-shirt...</p>",
  "sku": "TSHIRT-CLASSIC",
  "price": "24.00",
  "regular_price": "28.00",
  "sale_price": "24.00",
  "price_html": "<del>...</del> <ins>...</ins>",
  "on_sale": true,
  "purchasable": true,
  "total_sales": 342,
  "tax_status": "taxable",
  "manage_stock": true,
  "stock_quantity": 118,
  "stock_status": "instock",
  "weight": "0.2",
  "dimensions": { "length": "28", "width": "20", "height": "2" },
  "average_rating": "4.60",
  "rating_count": 87,
  "related_ids": [102, 118, 145, 201, 233],
  "categories": [{ "id": 15, "name": "Apparel", "slug": "apparel" }],
  "tags": [{ "id": 40, "name": "cotton", "slug": "cotton" }],
  "images": [{ "id": 55, "src": "https://example.com/wp-content/uploads/tshirt.jpg" }],
  "attributes": [{ "id": 1, "name": "Color", "options": ["Blue", "Black", "White"] }],
  "variations": [102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112],
  "meta_data": [
    { "id": 501, "key": "_yoast_wpseo_title", "value": "Classic T-Shirt | Example Store" },
    { "id": 503, "key": "_product_source", "value": "manual-import" }
  ],
  "_links": { "self": [{ "href": "https://example.com/wp-json/wc/v3/products/101" }] }
}
```

**With ShopMobi** — `?fields=id,name,price,images` added to the same call:

```json
{
  "id": 101,
  "name": "Classic T-Shirt",
  "price": "24.00",
  "images": [{ "id": 55, "src": "https://example.com/wp-content/uploads/tshirt.jpg" }]
}
```

| | Without ShopMobi | With ShopMobi | Change |
|---|---|---|---|
| Top-level fields | 66 | 4 | -94% |
| Payload for one product (minified) | ~2.8 KB | ~130 B | **-95%** |
| Payload for a 20-product listing screen | ~56 KB | ~2.5 KB | **-95%** |

Same endpoint. Same authentication. Same field names and types on the wire. The only thing that changes is how much of the response you asked for — multiply that across every list screen, pull-to-refresh, and background sync in your app and the savings compound fast.

= Supported Endpoints =

| Endpoint | Filtered on |
|---|---|
| `GET/POST /wc/v3/products`, `GET/PUT/DELETE /wc/v3/products/{id}` | Product object |
| `GET /wc/v3/products/{product_id}/variations`, `.../variations/{id}` | Product variation object |
| `GET/POST /wc/v3/orders`, `GET/PUT/DELETE /wc/v3/orders/{id}` | Order object |
| `GET /wc/v3/orders/{order_id}/refunds`, `.../refunds/{id}` | Order refund object |
| `GET/POST /wc/v3/customers`, `GET/PUT/DELETE /wc/v3/customers/{id}` | Customer object |

= Examples =

**Example 1 — Only the fields a product list screen needs**

```bash
curl "https://example.com/wp-json/wc/v3/products?fields=id,name,price,images" \
  -u consumer_key:consumer_secret
```

Response (every item in the array is trimmed the same way):

```json
[
  {
    "id": 101,
    "name": "Classic T-Shirt",
    "price": "24.00",
    "images": [{ "id": 55, "src": "https://example.com/wp-content/uploads/tshirt.jpg" }]
  }
]
```

Without filtering, the same request returns 50+ fields per product (descriptions, tax data, dimensions, all meta, `_links`, etc.).

**Example 2 — Header form, single product, with enhanced variations**

```bash
curl https://example.com/wp-json/wc/v3/products/101 \
  -u consumer_key:consumer_secret \
  -H "X-WC-Fields: id,name,price,variations"
```

Because the variation enhancer runs before field filtering, `variations` here returns full variation objects (SKU, pricing, stock, attributes, image) instead of raw IDs:

```json
{
  "id": 101,
  "name": "Classic T-Shirt",
  "price": "24.00",
  "variations": [
    {
      "id": 102,
      "sku": "TSHIRT-BLU-M",
      "on_sale": false,
      "regular_price": 24.0,
      "sale_price": 0,
      "quantity": 18,
      "stock_status": "instock",
      "attributes": [{ "name": "Color", "slug": "color", "option": "blue" }],
      "image": "https://example.com/wp-content/uploads/tshirt-blue.jpg"
    }
  ]
}
```

**Example 3 — Exclude heavy fields instead of allow-listing**

```bash
curl "https://example.com/wp-json/wc/v3/orders?except_fields=meta_data,_links,tax_lines" \
  -u consumer_key:consumer_secret
```

Every field WooCommerce normally returns for an order is included **except** `meta_data`, `_links`, and `tax_lines`.

**Example 4 — Customers, header exclude form**

```bash
curl https://example.com/wp-json/wc/v3/customers/12 \
  -u consumer_key:consumer_secret \
  -H "X-WC-Except: meta_data,_links,billing,shipping"
```

**Example 5 — Header takes priority over query parameter**

```bash
curl "https://example.com/wp-json/wc/v3/products?fields=id,name,description,short_description,type,status" \
  -u consumer_key:consumer_secret \
  -H "X-WC-Fields: id,price"
```

Only `id` and `price` are returned — the `fields` query parameter is ignored because `X-WC-Fields` was also sent.

== Custom Endpoints ==

All custom endpoints are registered under the `shopmobi/v1` namespace — the auth, profile, password reset, store info, and payment routes an SPA or mobile app needs, built and secured for you, so you don't need a second plugin alongside ShopMobi to cover them.

All custom endpoints live under this base URL:

`https://your-site.com/wp-json/shopmobi/v1`

= Quick Reference =

| Method | Endpoint | Auth required | Description |
|--------|----------|---------------|-------------|
| POST | `/users/login` | No | Cookie-based login |
| POST | `/users/register` | No | Customer registration |
| POST | `/users/update-profile` | Yes | Update name and phone |
| POST | `/users/reset-password/generate` | No | Email a password reset key |
| POST | `/users/reset-password/verify` | No | Verify key and set new password |
| GET  | `/general-settings` | No | Country, currency, active gateways |
| GET  | `/store-location` | Yes | Store address |
| GET  | `/payment-gateways` | No | Active payment gateways |
| POST | `/stripe-payment` | Yes | Create Stripe PaymentIntent + EphemeralKey |

= Authentication Model =

* **Public** endpoints need no authentication.
* **Requires login** endpoints check `current_user_can( 'read' )` — the request must carry a valid WordPress session. Use one of:
  * WordPress cookie authentication with an `X-WP-Nonce` header (browser / webview clients), or
  * [Application Passwords](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/) via HTTP Basic Auth (native / mobile clients).
* `POST /users/login` authenticates via WordPress's `wp_signon()`, which sets standard WordPress auth cookies on success. It does **not** return a bearer token — pair it with cookie-based session handling, or front it with a JWT plugin if your app needs stateless token auth.

= Authentication Endpoints =

**`POST /users/login`**

Log in with a WordPress username and password. Rate limited to 5 attempts per 5 minutes, tracked independently by username and by server IP.

Request body (JSON):

```json
{
  "username": "jane",
  "password": "correct-horse-battery-staple"
}
```

Success — `200 OK`:

```json
{
  "user": {
    "ID": 12,
    "user_login": "jane",
    "user_email": "jane@example.com",
    "display_name": "Jane Doe",
    "roles": ["customer"]
  },
  "meta": {
    "first_name": "Jane",
    "last_name": "Doe",
    "nickname": "jane",
    "user_phone": "+1 555 0100"
  }
}
```

> **Note:** `user` is the raw WordPress `WP_User` object serialized to JSON. Besides the fields shown above it also carries internal fields (e.g. `data`, `allcaps`, `cap_key`). Trim the response to what your client needs before exposing it further.

Errors:

| Status | Code | When |
|---|---|---|
| 422 | `username_required` | `username` missing or empty |
| 422 | `password_required` | `password` missing or empty |
| 429 | `too_many_attempts` | Rate limit exceeded for this username or IP |
| 403 | `login_failed` | Invalid credentials (message passed through from WordPress) |

**`POST /users/register`**

Create a new customer account. Rate limited to 3 attempts per hour per server IP.

Request body (JSON):

```json
{
  "username": "jane",
  "email": "jane@example.com",
  "password": "correct-horse-battery-staple",
  "name": "Jane Doe"
}
```

`name` is optional and, if provided, is split on the first space into `first_name` / `last_name` user meta.

Success — `200 OK`:

```json
{
  "code": 200,
  "message": "Registration successful.",
  "user": {
    "user": { "ID": 12, "user_login": "jane", "user_email": "jane@example.com", "display_name": "jane" },
    "meta": { "first_name": "Jane", "last_name": "Doe", "nickname": "jane", "user_phone": "" }
  }
}
```

The new user is assigned the `customer` role.

Errors:

| Status | Code | When |
|---|---|---|
| 422 | `username_required` / `email_required` / `password_required` | A required field is missing or empty |
| 409 | `409` | Username or email already exists |
| 429 | `too_many_attempts` | Rate limit exceeded for this IP |

**`POST /users/update-profile`** — *requires login*

Update the current user's first name, last name, and phone number.

Request body (JSON):

```json
{
  "first_name": "Jane",
  "last_name": "Doe",
  "user_phone": "+1 555 0100"
}
```

Success — `200 OK`:

```json
{
  "message": "Profile updated successfully.",
  "user": {
    "user": { "ID": 12, "user_login": "jane", "user_email": "jane@example.com" },
    "meta": { "first_name": "Jane", "last_name": "Doe", "nickname": "jane", "user_phone": "+1 555 0100" }
  }
}
```

Errors:

| Status | Code | When |
|---|---|---|
| 422 | `first_name_required` / `last_name_required` / `user_phone_required` | A required field is missing or empty |
| 401 / 403 | `rest_forbidden` | Not authenticated |

= Password Reset Endpoints =

**`POST /users/reset-password/generate`**

Sends a password reset key to the user's email using WordPress's native `get_password_reset_key()` flow. Accepts either a username or an email address.

Request body (JSON):

```json
{ "username": "jane" }
```

Success — `200 OK` (always returns a generic message, even for unknown accounts, to avoid username enumeration):

```json
{ "status": true, "message": "If that account exists, a reset key has been sent." }
```

Errors:

| Status | Code | When |
|---|---|---|
| 400 | `missing_username` | `username` missing or empty |
| 500 | `reset_key_error` | WordPress could not generate a reset key |

**`POST /users/reset-password/verify`**

Verifies the reset key emailed to the user and sets a new password via WordPress's native `check_password_reset_key()` / `reset_password()`.

Request body (JSON):

```json
{
  "username": "jane",
  "key": "the-key-from-the-email",
  "new_password": "a-new-strong-password"
}
```

Success — `200 OK`:

```json
{ "status": true, "message": "Password updated successfully." }
```

Errors:

| Status | Code | When |
|---|---|---|
| 400 | `missing_params` | `username`, `key`, or `new_password` missing |
| 400 | `invalid_key` | Key is invalid or expired (default expiry: 24 hours) |

= Store Information Endpoints =

**`GET /general-settings`** — public

Returns the store's country, currency formatting, and currently active payment gateways.

Success — `200 OK`:

```json
{
  "country": {
    "code": "US",
    "name": "United States (US)",
    "state": "CA",
    "states": [{ "label": "California", "value": "CA" }]
  },
  "currency": {
    "code": "USD",
    "symbol": "$",
    "position": "left",
    "decimal_separator": ".",
    "thousand_separator": ",",
    "decimals": 2
  },
  "gateways": [
    {
      "id": "stripe",
      "title": "Credit Card (Stripe)",
      "description": "Pay with your card.",
      "instructions": "",
      "order": "0"
    }
  ]
}
```

**`GET /store-location`** — *requires login*

Success — `200 OK`:

```json
{
  "address": "123 Market St",
  "city": "San Francisco",
  "postcode": "94103",
  "country": "US:CA"
}
```

**`GET /payment-gateways`** — public

A lighter version of the `gateways` array from `/general-settings` (no description/instructions).

Success — `200 OK`:

```json
[
  { "id": "stripe", "title": "Credit Card (Stripe)", "order": "0" },
  { "id": "cod", "title": "Cash on Delivery", "order": "1" }
]
```

= Payment Endpoints =

**`POST /stripe-payment`** — *requires login*

Creates a Stripe `PaymentIntent` and `EphemeralKey` for use with Stripe's mobile SDKs (`PaymentSheet`). Requires Stripe API keys to be configured under **WooCommerce > API Optimizer**.

Request body (JSON):

```json
{ "order_amount": 49 }
```

`order_amount` is a whole number in the store's currency major unit (e.g. dollars) and is converted to the smallest currency unit (`× 100`) before being sent to Stripe.

> **Note:** the `× 100` conversion assumes a 2-decimal currency (USD, EUR, etc.). It is not correct for zero-decimal currencies (e.g. JPY) — adjust before using this endpoint on a zero-decimal store.

Success — `200 OK`:

```json
{
  "paymentIntent": "pi_3P...secret_...",
  "ephemeralKey": "ek_test_...",
  "customer": "cus_P...",
  "publishableKey": "pk_live_..."
}
```

The Stripe customer ID is created on first use and cached in the `stripe_cust_id` user meta for reuse on subsequent payments.

Errors:

| Status | Code | When |
|---|---|---|
| 500 | `stripe_not_configured` | No Stripe secret key saved in plugin settings |
| 500 | `stripe_sdk_missing` | `vendor/` not installed (`composer install` not run) |
| 401 / 403 | `rest_forbidden` | Not authenticated |

> **Note:** exceptions raised by the Stripe SDK itself (declined card setup issues, invalid API key, network errors, etc.) are not currently caught, so a failed Stripe API call surfaces as a generic REST `500` error rather than a structured Stripe error payload.

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

No. This plugin adds a filtering layer on top of the standard WooCommerce REST API, and adds separate custom endpoints under `shopmobi/v1` alongside it. All existing WooCommerce authentication, permissions, and hooks still apply. See **Field Filtering** and **Custom Endpoints** above for full details.

= What happens if I stop using field filtering — is there anything to migrate? =

Nothing. Filtering only runs when a request includes `fields`/`X-WC-Fields` or `except_fields`/`X-WC-Except`. Stop sending those and every endpoint returns WooCommerce's normal, full response — nothing to roll back or reconfigure.

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
* Temporary password reset codes and expiry timestamps — handled entirely by WordPress core, deleted/invalidated automatically after use

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
* **Password Reset Keys** — handled entirely by WordPress core (`get_password_reset_key()` / `reset_password()`). No custom data is stored in user meta by this plugin.

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
