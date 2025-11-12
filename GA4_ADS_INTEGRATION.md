# Google Ads & GA4 Integration (Bagisto)

This document explains how to configure, integrate, use, and troubleshoot the Google Analytics 4 (GA4) and Google Ads conversion tracking added to your Bagisto shop.

## Overview

- Loads Google `gtag.js` based on admin configuration.
- Initializes GA4 and Google Ads properties.
- Provides a frontend plugin (`window.GAIntegration`) with helpers for standard and custom events.
- Tracks key ecommerce events:
  - `add_to_cart` on product detail and product cards
  - `purchase` on the checkout success page
  - Google Ads `conversion` for purchases

## Configuration (Admin Panel)

Go to: `Settings → General → Content → Google Analytics & Ads`

- `Enable GA4`: turn on GA4 tracking
- `GA4 Measurement ID`: e.g. `G-XXXXXXXX`
- `GA4 API Secret (optional)`: only needed for Measurement Protocol server-side usage
- `Enable Google Ads`: turn on Ads conversion tracking
- `Google Ads Conversion ID`: e.g. `AW-XXXXXXXX`
- `Google Ads Purchase Label`: conversion label ID
- `Debug Mode`: log debug messages to console

Notes:
- Validation enforces correct ID formats and only requires fields when the respective feature is enabled.
- Channel-based currency is automatically passed to GA4 events.

## Frontend Implementation

The following pieces are added:

- `shop::components.tracking.google` partial is included in the base layout head to load `gtag.js` and expose `window.__GA_CONFIG__`.
- New Vue plugin `plugins/analytics.js` that mounts `window.GAIntegration` with:
  - `trackAddToCart(payload)`
  - `trackPurchase(payload)`
  - `trackAdsPurchase(payload)`
  - `trackCustomEvent(name, params)`
  - `debugLog(...args)`

### Event Hooks

- Product detail `add_to_cart`: hooked after successful cart add to emit GA4 `add_to_cart` with item payload.
- Product card `add_to_cart`: hooked after successful cart add to emit GA4 `add_to_cart`.
- Checkout success: emits GA4 `purchase` with `transaction_id`, `value`, `currency`, and item list; emits Google Ads `conversion` for purchase.

## Validation & Testing

1. Enable GA4 and/or Ads in Admin and set IDs.
2. Open your storefront in an incognito window.
3. Use Chrome DevTools → Console to verify logs when `Debug Mode` is enabled.
4. GA4 DebugView: use Google Analytics DebugView to see `add_to_cart` and `purchase` events in near real-time.
5. Ads conversion: confirm events using Google Ads Diagnostics (may require some time and traffic).

### Cross-Browser

- Tested on Chromium-based browsers; ensure no ad-blockers that suppress `gtag.js`.
- For Safari and Firefox, confirm `gtag.js` loads and no CSP blocks.

## Troubleshooting

- No events in GA4:
  - Ensure Measurement ID is correct and GA4 is enabled.
  - Confirm `gtag.js` loads (Network tab) and no CSP errors.
  - Check console for `[GA]` debug logs.

- Ads conversion not recorded:
  - Verify Conversion ID and Purchase Label.
  - Check that events include `send_to` via the plugin.
  - Make sure consent mode or cookie banners are not blocking.

- Wrong currency or value:
  - Channel currency is used; on success page, order currency is used.
  - Inspect payloads in console logs when Debug Mode is on.

## Best Practices

- Keep tracking code minimal in views and centralize logic in the plugin.
- Use GA4 item schema consistently: `item_id`, `item_name`, `quantity`, `price`.
- Avoid double-firing purchase events; only emit on the success page.

## Optional Extensions

- Add server-side Measurement Protocol forwarding using `ga4_api_secret`.
- Implement additional GA4 events: `view_item`, `begin_checkout`, `add_payment_info`.
- Integrate consent mode signals if GDPR cookie banners are enabled.