# ShopMobi – API Optimizer for WooCommerce

> Building an SPA or mobile app on WooCommerce? Stop receiving 50+ fields when your screen needs 3 — and stop stitching together a JWT plugin, a filtering snippet, and a Stripe integration to get there. ShopMobi is the one plugin: field filtering + auth + payments, on the REST API you already use.

---

ShopMobi is a full-stop, all-in-one WooCommerce REST API layer for developers building **single-page apps, Flutter/React Native apps, or any headless WooCommerce frontend**. Instead of combining several plugins and custom code to get a mobile-ready API, you install one and get both of the following:

1. **[Field Filtering](#field-filtering)** — trim any existing `wc/v3` response down to exactly the fields your screen needs. No new routes, same auth, smaller payloads.
2. **[Custom Endpoints](#custom-endpoints)** — ready-made `shopmobi/v1` routes for login, registration, password reset, store info, and Stripe payments, so your app's auth and checkout flows don't need a second plugin.

### At a glance

```bash
curl "https://your-site.com/wp-json/wc/v3/products/101?fields=id,name,price" -u ck:cs
```

| | Default WooCommerce | With ShopMobi |
|---|---|---|
| Fields returned per product | 66 | exactly what you asked for |
| Payload, one product | ~2.8 KB | ~130 B (**−95%**) |
| New endpoints to learn | — | none — same `wc/v3` route |

Same endpoint, same credentials — just one query param or header added. Full breakdown with real JSON: [With vs. without ShopMobi](#with-vs-without-shopmobi).

## Table of Contents

- [Why ShopMobi?](#why-shopmobi)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Field Filtering](#field-filtering)
  - [Quick reference](#quick-reference)
  - [How field filtering works](#how-field-filtering-works)
  - [Product variation enhancement](#product-variation-enhancement)
  - [With vs. without ShopMobi](#with-vs-without-shopmobi)
  - [Supported endpoints](#supported-endpoints)
  - [Examples](#examples)
- [Custom Endpoints](#custom-endpoints)
  - [Quick reference](#quick-reference-1)
  - [Authentication model](#authentication-model)
  - [Authentication endpoints](#authentication-endpoints)
  - [Password reset endpoints](#password-reset-endpoints)
  - [Store information endpoints](#store-information-endpoints)
  - [Payment endpoints](#payment-endpoints)
- [FAQ](#faq)
- [Third-Party Services](#third-party-services)
- [License](#license)

## Why ShopMobi?

Built for developers building **SPAs and mobile apps on top of WooCommerce** — where every extra field is a field your app has to receive, parse, store, and, on a metered connection, pay for in bytes and battery.

- **One plugin instead of three.** A headless WooCommerce frontend usually needs a JWT/auth plugin, hand-written response-filtering code, and a separate Stripe integration, bolted together and kept in sync. ShopMobi ships all of it as one tested package.
- **Purpose-built for SPA and mobile clients.** Flutter, React Native, and native apps pay for every extra byte in load time, memory, and mobile data. A product screen that needs 4 fields shouldn't have to ship 60+.
- **GraphQL-like control without leaving REST.** Pick exactly the fields you want per request — no schema to define, no query language to learn, no separate GraphQL server to run and secure.
- **Auth, password reset, and Stripe payments, done for you.** Login, registration, profile updates, password reset, and Stripe `PaymentIntent`/`EphemeralKey` creation ship as ready-made `shopmobi/v1` endpoints, so you're not hand-rolling and re-securing this for every app.

More on how it works and what it costs to adopt: [How field filtering works](#how-field-filtering-works), [With vs. without ShopMobi](#with-vs-without-shopmobi), and the [FAQ](#faq).

## Requirements

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+

## Installation

**Option A — Pre-built release (recommended)**

Download the latest release zip from the [Releases](../../releases) page (includes `vendor/`), then upload via **Plugins > Add New > Upload Plugin**.

**Option B — From source**

```bash
git clone https://github.com/hammadev2/api-optimizer-for-woocommerce.git
cd api-optimizer-for-woocommerce
composer install --no-dev --optimize-autoloader
```

Then copy the folder to `wp-content/plugins/` and activate.

## Configuration

Go to **WooCommerce > API Optimizer** to enter your Stripe API keys. Field filtering and all other features work without any configuration.

---

## Field Filtering

Field filtering applies **on top of** the standard WooCommerce REST API — there are no new routes. You call the same `wc/v3` endpoints, authenticated the same way (consumer key/secret or OAuth1.0a), and opt into a smaller response per request. This is the layer that lets your SPA or mobile app request exactly the shape it renders, instead of parsing and discarding the rest.

### Quick reference

| Method | Include fields | Exclude fields |
|--------|----------------|----------------|
| Header | `X-WC-Fields: id,name,price` | `X-WC-Except: meta_data` |
| Query param | `?fields=id,name,price` | `?except_fields=meta_data` |

Header takes priority when both are present.

### How field filtering works

1. Your app calls a standard `wc/v3` endpoint exactly as it does today — same URL, same auth.
2. You add one of two things to the request: an **include list** (`fields` / `X-WC-Fields`) naming the fields you want, or an **exclude list** (`except_fields` / `X-WC-Except`) naming the fields you don't. Both are comma-separated field names.
3. WooCommerce builds its normal, full response object first — every hook, capability check, and sanitization rule runs exactly as it would without ShopMobi installed.
4. Right before that response is sent, ShopMobi reads your include/exclude list and keeps (or drops) the matching **top-level keys** of the response — e.g. `id`, `name`, `price`, `images`. Nested data like `images` or `variations` is returned as WooCommerce built it when you ask for that field; ShopMobi doesn't reach inside it.
5. This happens per resource, so a collection endpoint (a product list) is filtered the same way as a single-resource endpoint (one product) — every item comes back with the same trimmed shape.
6. If both an include and exclude parameter are sent, **both apply**: the response is trimmed to the include list first, then any excluded keys are removed from what's left — so an excluded field never comes back even if it was also in the include list. If both a header and a query parameter are sent for the same direction, **the header wins**.
7. If you use an include list, remember to add `id` explicitly if your client needs it — it is not added automatically.

The upshot: identical endpoint, identical auth, identical field names and types on the wire — just fewer of them.

### Product variation enhancement

Variation IDs in product responses are automatically replaced with full objects containing attributes, pricing, stock status, and image URL — the shape a product page actually renders, not a list of IDs that costs one extra request each to resolve.

This runs *before* field filtering, so requesting `fields=variations` returns the enhanced objects directly. See it in context in [Example — header form on a single product, with enhanced variations](#examples) below.

### With vs. without ShopMobi

Same request, same endpoint, same credentials — `GET /wp-json/wc/v3/products/101`. The only difference is one query string.

| | Without ShopMobi | With ShopMobi | Change |
|---|---|---|---|
| Top-level fields | 66 | 4 | −94% |
| Payload for one product (minified) | ~2.8 KB | ~130 B | **−95%** |
| Payload for a 20-product listing screen | ~56 KB | ~2.5 KB | **−95%** |

**With ShopMobi** — `?fields=id,name,price,images` added to the same call:

```json
{
  "id": 101,
  "name": "Classic T-Shirt",
  "price": "24.00",
  "images": [{ "id": 55, "src": "https://example.com/wp-content/uploads/tshirt.jpg" }]
}
```

<details>
<summary><strong>See the full unfiltered response this is trimmed from</strong> — the standard WooCommerce response, 66 top-level fields</summary>

```json
{
  "id": 101,
  "name": "Classic T-Shirt",
  "slug": "classic-t-shirt",
  "permalink": "https://example.com/product/classic-t-shirt/",
  "date_created": "2025-01-14T10:22:03",
  "date_created_gmt": "2025-01-14T10:22:03",
  "date_modified": "2025-06-02T08:11:47",
  "date_modified_gmt": "2025-06-02T08:11:47",
  "type": "variable",
  "status": "publish",
  "featured": false,
  "catalog_visibility": "visible",
  "description": "<p>A soft, breathable cotton t-shirt...</p>",
  "short_description": "<p>Soft cotton tee, multiple colors and sizes.</p>",
  "sku": "TSHIRT-CLASSIC",
  "price": "24.00",
  "regular_price": "28.00",
  "sale_price": "24.00",
  "price_html": "<del>...</del> <ins>...</ins>",
  "on_sale": true,
  "purchasable": true,
  "total_sales": 342,
  "virtual": false,
  "downloadable": false,
  "downloads": [],
  "tax_status": "taxable",
  "tax_class": "",
  "manage_stock": true,
  "stock_quantity": 118,
  "stock_status": "instock",
  "backorders": "no",
  "weight": "0.2",
  "dimensions": { "length": "28", "width": "20", "height": "2" },
  "shipping_class": "standard",
  "reviews_allowed": true,
  "average_rating": "4.60",
  "rating_count": 87,
  "related_ids": [102, 118, 145, 201, 233],
  "cross_sell_ids": [301, 302],
  "categories": [{ "id": 15, "name": "Apparel", "slug": "apparel" }],
  "tags": [{ "id": 40, "name": "cotton", "slug": "cotton" }],
  "images": [{ "id": 55, "src": "https://example.com/wp-content/uploads/tshirt.jpg" }],
  "attributes": [{ "id": 1, "name": "Color", "options": ["Blue", "Black", "White"] }],
  "variations": [102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112],
  "meta_data": [
    { "id": 501, "key": "_yoast_wpseo_title", "value": "Classic T-Shirt | Example Store" },
    { "id": 503, "key": "_product_source", "value": "manual-import" }
  ],
  "_links": {
    "self": [{ "href": "https://example.com/wp-json/wc/v3/products/101" }],
    "collection": [{ "href": "https://example.com/wp-json/wc/v3/products" }]
  }
  /* … 66 top-level fields in total for this product */
}
```

</details>

That's the whole trade: one query string vs. every extra field a mobile client will never render — parsed, held in memory, and paid for on a metered connection anyway. Multiply it across every list screen, pull-to-refresh, and background sync in your app, and the endpoint/auth stay exactly the same either way — you just install the plugin.

### Supported endpoints

| Endpoint | Filtered on |
|---|---|
| `GET/POST /wc/v3/products`, `GET/PUT/DELETE /wc/v3/products/{id}` | Product object |
| `GET /wc/v3/products/{product_id}/variations`, `.../variations/{id}` | Product variation object |
| `GET/POST /wc/v3/orders`, `GET/PUT/DELETE /wc/v3/orders/{id}` | Order object |
| `GET /wc/v3/orders/{order_id}/refunds`, `.../refunds/{id}` | Order refund object |
| `GET/POST /wc/v3/customers`, `GET/PUT/DELETE /wc/v3/customers/{id}` | Customer object |

### Examples

**Example — only the fields a product list screen needs**

```bash
curl "https://example.com/wp-json/wc/v3/products?fields=id,name,price,images" \
  -u consumer_key:consumer_secret
```

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

**Example — header form on a single product, with enhanced variations**

```bash
curl https://example.com/wp-json/wc/v3/products/101 \
  -u consumer_key:consumer_secret \
  -H "X-WC-Fields: id,name,price,variations"
```

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

**Example — exclude heavy fields instead of allow-listing**

```bash
curl "https://example.com/wp-json/wc/v3/orders?except_fields=meta_data,_links,tax_lines" \
  -u consumer_key:consumer_secret
```

**Example — exclude via header, on customers**

```bash
curl https://example.com/wp-json/wc/v3/customers/12 \
  -u consumer_key:consumer_secret \
  -H "X-WC-Except: meta_data,_links,billing,shipping"
```

**Example — header overrides query parameter**

```bash
curl "https://example.com/wp-json/wc/v3/products?fields=id,name,description,type" \
  -u consumer_key:consumer_secret \
  -H "X-WC-Fields: id,price"
```

Only `id` and `price` are returned — `X-WC-Fields` wins and the `fields` query parameter is ignored.

---

## Custom Endpoints

All custom endpoints are registered under the `shopmobi/v1` namespace — the auth, profile, password reset, store info, and payment routes an SPA or mobile app needs, built and secured for you, so you don't need a second plugin alongside ShopMobi to cover them.

Base URL: `https://your-site.com/wp-json/shopmobi/v1`

### Quick reference

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

### Authentication model

- **Public** endpoints need no authentication.
- **Requires login** endpoints check `current_user_can( 'read' )` — the request needs a valid WordPress session via:
  - WordPress cookie authentication + `X-WP-Nonce` header (browser/webview clients), or
  - [Application Passwords](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/) via HTTP Basic Auth (native/mobile clients).
- `POST /users/login` authenticates via WordPress's `wp_signon()`, which sets standard WordPress auth cookies. It does **not** return a bearer token — pair it with cookie-based sessions, or front it with a JWT plugin for stateless mobile auth.

### Authentication endpoints

<details>
<summary><code>POST /users/login</code> — log in with username + password</summary>

Rate limited to 5 attempts / 5 minutes, tracked independently by username and by server IP.

**Request**

```bash
curl -X POST https://example.com/wp-json/shopmobi/v1/users/login \
  -H "Content-Type: application/json" \
  -d '{"username":"jane","password":"correct-horse-battery-staple"}'
```

**Response `200 OK`**

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

> `user` is the raw WordPress `WP_User` object serialized to JSON — it also carries internal fields (`data`, `allcaps`, `cap_key`, etc.) beyond what's shown above. Trim it before exposing to an untrusted client.

**Errors**

| Status | Code | When |
|---|---|---|
| 422 | `username_required` | `username` missing or empty |
| 422 | `password_required` | `password` missing or empty |
| 429 | `too_many_attempts` | Rate limit exceeded for this username or IP |
| 403 | `login_failed` | Invalid credentials |

</details>

<details>
<summary><code>POST /users/register</code> — create a new customer account</summary>

Rate limited to 3 attempts / hour per server IP. `name` is optional; if present it's split on the first space into `first_name` / `last_name`.

**Request**

```bash
curl -X POST https://example.com/wp-json/shopmobi/v1/users/register \
  -H "Content-Type: application/json" \
  -d '{"username":"jane","email":"jane@example.com","password":"secret","name":"Jane Doe"}'
```

**Response `200 OK`**

```json
{
  "code": 200,
  "message": "Registration successful.",
  "user": {
    "user": { "ID": 12, "user_login": "jane", "user_email": "jane@example.com" },
    "meta": { "first_name": "Jane", "last_name": "Doe", "nickname": "jane", "user_phone": "" }
  }
}
```

New users are assigned the `customer` role.

**Errors**

| Status | Code | When |
|---|---|---|
| 422 | `username_required` / `email_required` / `password_required` | Required field missing or empty |
| 409 | `409` | Username or email already exists |
| 429 | `too_many_attempts` | Rate limit exceeded for this IP |

</details>

<details>
<summary><code>POST /users/update-profile</code> — update name and phone (requires login)</summary>

**Request**

```bash
curl -X POST https://example.com/wp-json/shopmobi/v1/users/update-profile \
  -H "Content-Type: application/json" \
  -H "Cookie: wordpress_logged_in_xxx=..." \
  -H "X-WP-Nonce: <nonce>" \
  -d '{"first_name":"Jane","last_name":"Doe","user_phone":"+1 555 0100"}'
```

**Response `200 OK`**

```json
{
  "message": "Profile updated successfully.",
  "user": {
    "user": { "ID": 12, "user_login": "jane", "user_email": "jane@example.com" },
    "meta": { "first_name": "Jane", "last_name": "Doe", "nickname": "jane", "user_phone": "+1 555 0100" }
  }
}
```

**Errors**

| Status | Code | When |
|---|---|---|
| 422 | `first_name_required` / `last_name_required` / `user_phone_required` | Required field missing or empty |
| 401/403 | `rest_forbidden` | Not authenticated |

</details>

### Password reset endpoints

<details>
<summary><code>POST /users/reset-password/generate</code> — email a reset key</summary>

Accepts a username or an email address. Always returns a generic success message, even for unknown accounts, to avoid username enumeration.

**Request**

```bash
curl -X POST https://example.com/wp-json/shopmobi/v1/users/reset-password/generate \
  -H "Content-Type: application/json" \
  -d '{"username":"jane"}'
```

**Response `200 OK`**

```json
{ "status": true, "message": "If that account exists, a reset key has been sent." }
```

**Errors**

| Status | Code | When |
|---|---|---|
| 400 | `missing_username` | `username` missing or empty |
| 500 | `reset_key_error` | WordPress could not generate a reset key |

</details>

<details>
<summary><code>POST /users/reset-password/verify</code> — verify key and set a new password</summary>

**Request**

```bash
curl -X POST https://example.com/wp-json/shopmobi/v1/users/reset-password/verify \
  -H "Content-Type: application/json" \
  -d '{"username":"jane","key":"the-key-from-the-email","new_password":"a-new-strong-password"}'
```

**Response `200 OK`**

```json
{ "status": true, "message": "Password updated successfully." }
```

**Errors**

| Status | Code | When |
|---|---|---|
| 400 | `missing_params` | `username`, `key`, or `new_password` missing |
| 400 | `invalid_key` | Key is invalid or expired (default expiry: 24 hours) |

</details>

### Store information endpoints

<details>
<summary><code>GET /general-settings</code> — country, currency, active gateways (public)</summary>

**Request**

```bash
curl https://example.com/wp-json/shopmobi/v1/general-settings
```

**Response `200 OK`**

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
    { "id": "stripe", "title": "Credit Card (Stripe)", "description": "Pay with your card.", "instructions": "", "order": "0" }
  ]
}
```

</details>

<details>
<summary><code>GET /store-location</code> — store address (requires login)</summary>

**Request**

```bash
curl https://example.com/wp-json/shopmobi/v1/store-location \
  -H "Cookie: wordpress_logged_in_xxx=..." \
  -H "X-WP-Nonce: <nonce>"
```

**Response `200 OK`**

```json
{ "address": "123 Market St", "city": "San Francisco", "postcode": "94103", "country": "US:CA" }
```

</details>

<details>
<summary><code>GET /payment-gateways</code> — active payment gateways (public)</summary>

**Request**

```bash
curl https://example.com/wp-json/shopmobi/v1/payment-gateways
```

**Response `200 OK`**

```json
[
  { "id": "stripe", "title": "Credit Card (Stripe)", "order": "0" },
  { "id": "cod", "title": "Cash on Delivery", "order": "1" }
]
```

</details>

### Payment endpoints

<details>
<summary><code>POST /stripe-payment</code> — create a Stripe PaymentIntent (requires login)</summary>

Requires Stripe API keys configured under **WooCommerce > API Optimizer**. `order_amount` is a whole number in the store's currency major unit (e.g. dollars) and is multiplied by 100 before being sent to Stripe.

> The `× 100` conversion assumes a 2-decimal currency (USD, EUR, etc.) — it is not correct for zero-decimal currencies like JPY.

**Request**

```bash
curl -X POST https://example.com/wp-json/shopmobi/v1/stripe-payment \
  -H "Content-Type: application/json" \
  -H "Cookie: wordpress_logged_in_xxx=..." \
  -H "X-WP-Nonce: <nonce>" \
  -d '{"order_amount": 49}'
```

**Response `200 OK`**

```json
{
  "paymentIntent": "pi_3P...secret_...",
  "ephemeralKey": "ek_test_...",
  "customer": "cus_P...",
  "publishableKey": "pk_live_..."
}
```

The Stripe customer ID is created on first use and cached in the `stripe_cust_id` user meta for reuse.

**Errors**

| Status | Code | When |
|---|---|---|
| 500 | `stripe_not_configured` | No Stripe secret key saved in plugin settings |
| 500 | `stripe_sdk_missing` | `vendor/` not installed (`composer install` not run) |
| 401/403 | `rest_forbidden` | Not authenticated |

> Exceptions raised by the Stripe SDK itself (declined cards, invalid API key, network errors) aren't currently caught — a failed Stripe call surfaces as a generic `500` rather than a structured Stripe error.

</details>

## FAQ

**Does this replace the WooCommerce REST API?**
No. Field filtering is a layer on top of the standard `wc/v3` endpoints — same routes, same consumer key/secret or OAuth1.0a auth, same permissions and hooks. Custom Endpoints add separate `shopmobi/v1` routes alongside it, not instead of it.

**What happens if I stop using field filtering — is there anything to migrate?**
No. Filtering only runs when a request includes `fields`/`X-WC-Fields` or `except_fields`/`X-WC-Except`. Stop sending those and every endpoint returns WooCommerce's normal, full response — nothing to roll back or reconfigure.

**Does any of my store data get sent to ShopMobi?**
No. See [Third-Party Services](#third-party-services) below — the only third party involved is Stripe, and only when you've configured it and a client calls the payment endpoint.

## Third-Party Services

This plugin optionally connects to [Stripe](https://stripe.com) for payment processing. Stripe keys are entered by the site admin and stored in the WordPress database. No data is sent to ShopMobi.

- [Stripe Privacy Policy](https://stripe.com/privacy)
- [Stripe Terms of Service](https://stripe.com/legal)

## License

GPLv2 or later — see [LICENSE](LICENSE).
