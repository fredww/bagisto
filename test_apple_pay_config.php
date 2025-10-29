<?php

/**
 * Test script to verify Apple Pay configuration
 *
 * Run this script to check if your Apple Pay configuration is working correctly.
 * Remove this file after testing.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

// Bootstrap Laravel (minimal)
$app = new Container();
Container::setInstance($app);
Facade::setFacadeApplication($app);

// Load configuration
$config = include __DIR__ . '/packages/Webkul/Paypal/src/Config/paymentmethods.php';

echo "=== PayPal Apple Pay Configuration Test ===\n\n";

$paypalConfig = $config['paypal_smart_button'] ?? [];

echo "PayPal Smart Button Configuration:\n";
echo "- Title: " . ($paypalConfig['title'] ?? 'Not set') . "\n";
echo "- Description: " . ($paypalConfig['description'] ?? 'Not set') . "\n";
echo "- Client ID: " . ($paypalConfig['client_id'] ?? 'Not set') . "\n";
echo "- Client Secret: " . (empty($paypalConfig['client_secret']) ? 'Not set' : 'Set') . "\n";
echo "- Sandbox: " . ($paypalConfig['sandbox'] ? 'Enabled' : 'Disabled') . "\n";
echo "- Active: " . ($paypalConfig['active'] ? 'Enabled' : 'Disabled') . "\n";
echo "- Apple Pay Enabled: " . ($paypalConfig['enable_apple_pay'] ? 'Enabled' : 'Disabled') . "\n";
echo "- Apple Pay Merchant ID: " . ($paypalConfig['apple_pay_merchant_id'] ?? 'Not set') . "\n";
echo "- Accepted Currencies: " . ($paypalConfig['accepted_currencies'] ?? 'Not set') . "\n";

echo "\n=== Apple Pay Requirements Check ===\n";

// Check if Apple Pay is properly configured
$applePayEnabled = $paypalConfig['enable_apple_pay'] ?? false;
$clientId = $paypalConfig['client_id'] ?? '';
$acceptedCurrencies = $paypalConfig['accepted_currencies'] ?? '';

echo "✓ Apple Pay Configuration: " . ($applePayEnabled ? "ENABLED" : "DISABLED") . "\n";
echo "✓ PayPal Client ID: " . (empty($clientId) ? "MISSING" : "SET") . "\n";
echo "✓ Accepted Currencies: " . (empty($acceptedCurrencies) ? "MISSING" : "SET") . "\n";

// Check supported currencies
$supportedCurrencies = ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN'];
$currencyArray = explode(',', $acceptedCurrencies);
$supportedCurrencyCount = 0;

foreach ($currencyArray as $currency) {
    $currency = trim(strtoupper($currency));
    if (in_array($currency, $supportedCurrencies)) {
        $supportedCurrencyCount++;
    }
}

echo "✓ Apple Pay Supported Currencies: $supportedCurrencyCount/" . count($currencyArray) . "\n";

echo "\n=== Frontend Integration Check ===\n";

$bladeFile = __DIR__ . '/packages/Webkul/Paypal/src/Resources/views/checkout/onepage/paypal-smart-button.blade.php';
if (file_exists($bladeFile)) {
    $bladeContent = file_get_contents($bladeFile);

    $hasApplePayConfig = strpos($bladeContent, 'enable_apple_pay') !== false;
    $hasApplePayContainer = strpos($bladeContent, 'paypal-applepay-button-container') !== false;
    $hasApplePaySDK = strpos($bladeContent, 'enable-funding=applepay') !== false;
    $hasDeviceDetection = strpos($bladeContent, 'isApplePayAvailable') !== false;

    echo "✓ Apple Pay Configuration Variable: " . ($hasApplePayConfig ? "FOUND" : "MISSING") . "\n";
    echo "✓ Apple Pay Button Container: " . ($hasApplePayContainer ? "FOUND" : "MISSING") . "\n";
    echo "✓ Apple Pay SDK Parameter: " . ($hasApplePaySDK ? "FOUND" : "MISSING") . "\n";
    echo "✓ Device Detection Logic: " . ($hasDeviceDetection ? "FOUND" : "MISSING") . "\n";
} else {
    echo "✗ Blade template file not found\n";
}

echo "\n=== Summary ===\n";

$issues = [];

if (!$applePayEnabled) {
    $issues[] = "Apple Pay is disabled in configuration";
}

if (empty($clientId)) {
    $issues[] = "PayPal Client ID is not set";
}

if (empty($acceptedCurrencies)) {
    $issues[] = "Accepted currencies are not configured";
}

if ($supportedCurrencyCount === 0 && !empty($acceptedCurrencies)) {
    $issues[] = "No Apple Pay supported currencies configured";
}

if (empty($issues)) {
    echo "🎉 All Apple Pay configuration checks passed!\n";
    echo "\nNext steps:\n";
    echo "1. Configure your PayPal Developer Console to enable Apple Pay\n";
    echo "2. Set up Apple Pay domain verification\n";
    echo "3. Test on a supported device/browser\n";
} else {
    echo "⚠️  Configuration issues found:\n";
    foreach ($issues as $issue) {
        echo "- $issue\n";
    }
    echo "\nPlease fix these issues before testing Apple Pay functionality.\n";
}

echo "\nFor detailed setup instructions, see: APPLE_PAY_SETUP_GUIDE.md\n";
echo "Remove this file after testing.\n";