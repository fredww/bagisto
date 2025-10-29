# Apple Pay Setup Guide for PayPal Smart Button

This guide explains how to configure Apple Pay support for the PayPal Smart Button payment method in your Bagisto store.

## Prerequisites

1. **PayPal Business Account** with Apple Pay enabled
2. **SSL Certificate** (HTTPS) - Apple Pay requires secure connections
3. **Apple Developer Account** (for domain verification)
4. **Supported Browser** - Safari on iOS/macOS, Chrome on iOS 13+

## Configuration Steps

### 1. Enable Apple Pay in Admin Panel

1. Go to **Settings > Payment Methods** in your Bagisto admin
2. Find **PayPal Smart Button** and click to configure
3. Set the following fields:

```
✅ Active: Enable
✅ Client ID: Your PayPal Client ID
✅ Client Secret: Your PayPal Client Secret
✅ Enable Apple Pay: Check this box
✅ Apple Pay Merchant ID: Your Apple Pay Merchant ID (optional)
✅ Sandbox: Enable for testing, disable for production
✅ Accepted Currencies: USD,EUR,GBP (Apple Pay supported currencies)
```

### 2. PayPal Developer Console Setup

1. Go to [PayPal Developer Dashboard](https://developer.paypal.com/)
2. Select your application
3. In **Features > Payment Methods**, enable **Apple Pay**
4. Configure your Apple Pay settings:
   - Merchant ID
   - Domain verification
   - Supported currencies

### 3. Apple Developer Setup

1. Go to [Apple Developer Portal](https://developer.apple.com/)
2. Navigate to **Certificates, Identifiers & Profiles**
3. Create a **Merchant ID** if you don't have one
4. Register your domain for Apple Pay:
   - Add your domain to the merchant ID
   - Download the verification file
   - Upload it to your website's `.well-known/` directory

### 4. Domain Verification

Create the directory structure in your Bagisto public folder:
```bash
mkdir -p public/.well-known/
```

Upload the Apple Pay verification file to:
```
public/.well-known/apple-developer-merchantid-domain-association
```

### 5. Testing

**Sandbox Testing:**
1. Enable Sandbox mode in PayPal settings
2. Use a supported device (iPhone/iPad/Mac with Safari)
3. Test with a real Apple Card or test payment method

**Production Testing:**
1. Disable Sandbox mode
2. Use production PayPal credentials
3. Test with live Apple Pay transactions

## Configuration Options

### PayPal Smart Button Settings

| Setting | Description | Default |
|---------|-------------|---------|
| `enable_apple_pay` | Enable/disable Apple Pay | `false` |
| `apple_pay_merchant_id` | Your Apple Pay Merchant ID | empty |
| `accepted_currencies` | Comma-separated list of supported currencies | `USD,EUR,GBP` |
| `client_id` | PayPal Client ID | `sb` (sandbox) |
| `client_secret` | PayPal Client Secret | empty |
| `sandbox` | Enable sandbox mode | `true` |

## Supported Features

✅ **Device Detection** - Automatically detects Apple Pay availability
✅ **Responsive Design** - Adapts to mobile and desktop layouts
✅ **Error Handling** - Clear error messages for users
✅ **Security** - Uses PayPal's secure Apple Pay integration
✅ **Currency Support** - Multiple currencies supported

## Browser and Device Compatibility

### Supported Browsers:
- Safari (iOS 13+, macOS 10.15+)
- Chrome (iOS 14+)
- Firefox (iOS 14+)
- Edge (iOS 14+)

### Supported Devices:
- iPhone (iOS 13+)
- iPad (iOS 13+)
- Mac (macOS 10.15+ with Safari)
- Apple Watch (watchOS 6+)

## Troubleshooting

### Apple Pay Button Not Showing
1. Check if `enable_apple_pay` is enabled in settings
2. Verify you're using a supported browser/device
3. Ensure your site has SSL (HTTPS)
4. Check browser console for errors

### Domain Verification Failed
1. Verify the verification file is accessible at `/.well-known/apple-developer-merchantid-domain-association`
2. Check file permissions (should be publicly readable)
3. Ensure domain matches exactly in Apple Developer Portal

### Payment Processing Errors
1. Verify PayPal credentials are correct
2. Check if currency is supported by Apple Pay
3. Ensure merchant ID is properly configured
4. Review PayPal Developer Dashboard for errors

## Security Notes

- **SSL Required**: Apple Pay only works over HTTPS
- **Domain Verification**: Must be completed for production
- **Merchant ID**: Required for production environment
- **Currency Restrictions**: Only Apple Pay supported currencies work

## Technical Implementation

The Apple Pay integration uses:
- **PayPal Checkout SDK v2** with `enable-funding=applepay`
- **Automatic device detection** for Apple Pay availability
- **Separate button containers** for Apple Pay and standard PayPal
- **Shared backend processing** for all payment methods

## Support

For issues related to:
- **PayPal Integration**: Contact PayPal Developer Support
- **Apple Pay Setup**: Contact Apple Developer Support
- **Bagisto Integration**: Check Bagisto documentation or support channels