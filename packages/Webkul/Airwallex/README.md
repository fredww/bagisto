# Airwallex Payment Gateway for Bagisto

This package adds an Airwallex payment method to Bagisto, following Laravel and Bagisto module conventions.

## Configuration

- Go to `Settings > Payment Methods > Airwallex`.
- Set `Client ID`, `API Key`, `Base URL` (defaults: sandbox `https://api-demo.airwallex.com`, production `https://api.airwallex.com`).
- Optionally set `Merchant ID`, `Callback URL`, `Webhook Shared Secret`.
- Enable `Sandbox` for testing.
- Upload a logo if desired.

Environment fallbacks (useful for testing):
- `AIRWALLEX_CLIENT_ID`
- `AIRWALLEX_API_KEY`
- `AIRWALLEX_BASE_URL`
- `AIRWALLEX_SANDBOX`
- `AIRWALLEX_WEBHOOK_SECRET`

## Usage

- Select `Airwallex` at checkout; the system creates a Payment Link and redirects the shopper.
- Webhook `POST /airwallex/webhook` accepts Airwallex events with headers `x-nonce` and `x-signature` using the shared secret.
- Query payment status: `GET /airwallex/status/{intentId}`.
- Create refund: `POST /airwallex/refund` with JSON `{ payment_intent_id, amount, currency }`.

## Testing

- HTTP calls are implemented with Laravel `Http` and can be faked.
- Feature tests are in `packages/Webkul/Airwallex/tests/Feature`.

## Notes

- Follows PSR autoloading via Composer and Bagisto module registration.
- Images and translations are namespaced under `airwallex`.
