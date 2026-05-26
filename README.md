# API Optimizer for WooCommerce

> **Your Store Deserves a Better API.**
>
> Stop receiving 50+ fields when your app needs 3. ShopMobi gives your store GraphQL-like flexibility over REST — plus login & Stripe payments, out of the box.
>

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

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/wp-json/wp/v2/users/login` | Cookie-based login |
| POST | `/wp-json/wp/v2/users/register` | Customer registration |
| POST | `/wp-json/wp/v2/users/update-profile` | Update name and phone |
| POST | `/wp-json/wp/v2/users/reset-password/generate` | Send 4-digit email reset code |
| POST | `/wp-json/wp/v2/users/reset-password/verify` | Verify code and set new password |
| GET  | `/wp-json/wp/v2/general-settings` | Country, currency, store location, gateways |
| GET  | `/wp-json/wp/v2/payment-gateways` | Active payment gateways |
| POST | `/wp-json/wp/v2/stripe-payment` | Create Stripe PaymentIntent + EphemeralKey |

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
git clone https://github.com/hammadanwar/wc-api-optimizer.git
cd wc-api-optimizer
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
curl -X POST https://example.com/wp-json/wp/v2/users/register \
  -H "Content-Type: application/json" \
  -d '{"username":"john","email":"john@example.com","password":"secret","name":"John Doe"}'

# Create a Stripe PaymentIntent
curl -X POST https://example.com/wp-json/wp/v2/stripe-payment \
  -H "Content-Type: application/json" \
  -d '{"order_amount": 99, "user_id": 5}'
```

## License

GPLv2 or later — see [LICENSE](LICENSE).
