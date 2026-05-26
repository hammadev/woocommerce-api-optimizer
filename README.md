# ShopMobi – API Optimizer for WooCommerce

> Stop receiving 50+ fields when your app needs 3. ShopMobi gives your store GraphQL-like flexibility over REST — plus login & Stripe payments, out of the box.

---

A WordPress plugin that optimizes WooCommerce REST API responses with field filtering and adds custom endpoints for mobile and headless app integration.

## Features

### Field Filtering

Reduce response payload by requesting only the fields you need — on any WooCommerce REST API endpoint.

| Method | Include fields | Exclude fields |
|--------|----------------|----------------|
| Header | `X-WC-Fields: id,name,price` | `X-WC-Except: meta_data` |
| Query param | `?fields=id,name,price` | `?except_fields=meta_data` |

Header takes priority when both are present. Works on `/wc/v3/products`, `/wc/v3/orders`, `/wc/v3/customers`, and variations.

### Custom Endpoints

All custom endpoints are registered under the `shopmobi/v1` namespace.

| Method | Endpoint | Auth required | Description |
|--------|----------|---------------|-------------|
| POST | `/wp-json/shopmobi/v1/users/login` | No | Cookie-based login |
| POST | `/wp-json/shopmobi/v1/users/register` | No | Customer registration |
| POST | `/wp-json/shopmobi/v1/users/update-profile` | Yes | Update name and phone |
| POST | `/wp-json/shopmobi/v1/users/reset-password/generate` | No | Send 4-digit email reset code |
| POST | `/wp-json/shopmobi/v1/users/reset-password/verify` | No | Verify code and set new password |
| GET  | `/wp-json/shopmobi/v1/general-settings` | No | Country, currency, store location, gateways |
| GET  | `/wp-json/shopmobi/v1/payment-gateways` | No | Active payment gateways |
| POST | `/wp-json/shopmobi/v1/stripe-payment` | Yes | Create Stripe PaymentIntent + EphemeralKey |

### Product Variations

Variation IDs in product responses are automatically replaced with full objects containing attributes, pricing, stock status, and image URL.

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

## Usage Examples

```bash
# Get only id, name, and price for all products
curl https://example.com/wp-json/wc/v3/products \
  -H "Authorization: Basic xxx" \
  -H "X-WC-Fields: id,name,price"

# Exclude heavy fields from a single product
curl https://example.com/wp-json/wc/v3/products/123 \
  -H "Authorization: Basic xxx" \
  -H "X-WC-Except: meta_data,description,short_description"

# Register a new customer
curl -X POST https://example.com/wp-json/shopmobi/v1/users/register \
  -H "Content-Type: application/json" \
  -d '{"username":"john","email":"john@example.com","password":"secret","name":"John Doe"}'

# Login
curl -X POST https://example.com/wp-json/shopmobi/v1/users/login \
  -H "Content-Type: application/json" \
  -d '{"username":"john","password":"secret"}'

# Create a Stripe PaymentIntent (requires login cookie)
curl -X POST https://example.com/wp-json/shopmobi/v1/stripe-payment \
  -H "Content-Type: application/json" \
  -H "Cookie: wordpress_logged_in_xxx=..." \
  -d '{"order_amount": 99}'
```

## Third-Party Services

This plugin optionally connects to [Stripe](https://stripe.com) for payment processing. Stripe keys are entered by the site admin and stored in the WordPress database. No data is sent to ShopMobi.

- [Stripe Privacy Policy](https://stripe.com/privacy)
- [Stripe Terms of Service](https://stripe.com/legal)

## License

GPLv2 or later — see [LICENSE](LICENSE).
