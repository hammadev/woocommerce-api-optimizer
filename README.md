# ShopMobi – API Optimizer for WooCommerce

> Stop receiving 50+ fields when your app needs 3. ShopMobi gives your store GraphQL-like flexibility over REST — plus login & Stripe payments, out of the box.

---

A WordPress plugin that optimizes WooCommerce REST API responses with field filtering and adds custom endpoints for mobile and headless app integration.

It ships two independent things, each documented in full below:

1. **[Custom API Reference](#custom-api-reference)** — new routes under `shopmobi/v1` for auth, password reset, store settings, and Stripe payments.
2. **[Field Filtering Reference](#field-filtering-reference)** — a response-shaping layer on the *existing* `wc/v3` WooCommerce endpoints. No new routes, same auth, smaller payloads.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Custom API Reference](#custom-api-reference)
  - [Authentication model](#authentication-model)
  - [Authentication endpoints](#authentication-endpoints)
  - [Password reset endpoints](#password-reset-endpoints)
  - [Store information endpoints](#store-information-endpoints)
  - [Payment endpoints](#payment-endpoints)
- [Field Filtering Reference](#field-filtering-reference)
- [Third-Party Services](#third-party-services)
- [License](#license)

## Features

### Field Filtering

Reduce response payload by requesting only the fields you need — on any WooCommerce REST API endpoint.

| Method | Include fields | Exclude fields |
|--------|----------------|----------------|
| Header | `X-WC-Fields: id,name,price` | `X-WC-Except: meta_data` |
| Query param | `?fields=id,name,price` | `?except_fields=meta_data` |

Header takes priority when both are present. Works on `/wc/v3/products`, `/wc/v3/orders`, `/wc/v3/customers`, and variations. Full examples: [Field Filtering Reference](#field-filtering-reference).

### Custom Endpoints

All custom endpoints are registered under the `shopmobi/v1` namespace.

| Method | Endpoint | Auth required | Description |
|--------|----------|---------------|-------------|
| POST | `/wp-json/shopmobi/v1/users/login` | No | Cookie-based login |
| POST | `/wp-json/shopmobi/v1/users/register` | No | Customer registration |
| POST | `/wp-json/shopmobi/v1/users/update-profile` | Yes | Update name and phone |
| POST | `/wp-json/shopmobi/v1/users/reset-password/generate` | No | Email a password reset key |
| POST | `/wp-json/shopmobi/v1/users/reset-password/verify` | No | Verify key and set new password |
| GET  | `/wp-json/shopmobi/v1/general-settings` | No | Country, currency, active gateways |
| GET  | `/wp-json/shopmobi/v1/store-location` | Yes | Store address |
| GET  | `/wp-json/shopmobi/v1/payment-gateways` | No | Active payment gateways |
| POST | `/wp-json/shopmobi/v1/stripe-payment` | Yes | Create Stripe PaymentIntent + EphemeralKey |

Full request/response detail: [Custom API Reference](#custom-api-reference).

### Product Variations

Variation IDs in product responses are automatically replaced with full objects containing attributes, pricing, stock status, and image URL. This runs *before* field filtering, so requesting `fields=variations` returns the enhanced objects, not raw IDs.

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

Go to **WooCommerce > API Optimizer** to enter your Stripe API keys. All other features work without any configuration.

---

## Custom API Reference

Base URL: `https://your-site.com/wp-json/shopmobi/v1`

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

---

## Field Filtering Reference

Field filtering applies **on top of** the standard WooCommerce REST API — there are no new routes. You call the same `wc/v3` endpoints, authenticated the same way (consumer key/secret or OAuth1.0a), and opt into a smaller response per request.

- **Include only** — header `X-WC-Fields` or query param `fields`, comma-separated.
- **Exclude** — header `X-WC-Except` or query param `except_fields`, comma-separated.
- If both a header and query parameter are sent, **the header wins**.
- Filtering runs *after* WooCommerce builds the full response, so all WooCommerce hooks/permissions/sanitization still apply — this only trims the final payload.
- Works the same on collection endpoints (lists) and single-resource endpoints.
- If you use an include list, remember to add `id` explicitly if your client needs it.

**Supported endpoints**

| Endpoint | Filtered on |
|---|---|
| `GET/POST /wc/v3/products`, `/wc/v3/products/{id}` | Product object |
| `GET /wc/v3/products/{product_id}/variations`, `.../variations/{id}` | Product variation object |
| `GET/POST /wc/v3/orders`, `/wc/v3/orders/{id}` | Order object |
| `GET /wc/v3/orders/{order_id}/refunds`, `.../refunds/{id}` | Order refund object |
| `GET/POST /wc/v3/customers`, `/wc/v3/customers/{id}` | Customer object |

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

## Third-Party Services

This plugin optionally connects to [Stripe](https://stripe.com) for payment processing. Stripe keys are entered by the site admin and stored in the WordPress database. No data is sent to ShopMobi.

- [Stripe Privacy Policy](https://stripe.com/privacy)
- [Stripe Terms of Service](https://stripe.com/legal)

## License

GPLv2 or later — see [LICENSE](LICENSE).
