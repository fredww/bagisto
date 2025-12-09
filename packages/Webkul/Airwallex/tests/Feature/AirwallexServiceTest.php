<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Webkul\Airwallex\Services\AirwallexService;

uses(TestCase::class);

function seedAirwallexConfig(): void
{
    putenv('AIRWALLEX_CLIENT_ID=client_abc');
    putenv('AIRWALLEX_API_KEY=key_xyz');
    putenv('AIRWALLEX_BASE_URL=https://api-demo.airwallex.com');
    putenv('AIRWALLEX_SANDBOX=true');
    Config::set('services.airwallex.client_id', 'client_abc');
    Config::set('services.airwallex.api_key', 'key_xyz');
}

it('obtains access token', function () {
    seedAirwallexConfig();

    Http::fake([
        'https://api-demo.airwallex.com/api/v1/authentication/login' => Http::response(['token' => 't123'], 200),
    ]);

    $svc = new AirwallexService;
    expect($svc->obtainAccessToken())->toBe('t123');
});

it('creates payment link', function () {
    seedAirwallexConfig();

    Http::fake([
        'https://api-demo.airwallex.com/api/v1/authentication/login'    => Http::response(['token' => 't123'], 200),
        'https://api-demo.airwallex.com/api/v1/pa/payment_links/create' => Http::response(['url' => 'https://link.example'], 200),
    ]);

    $svc = new AirwallexService;
    $res = $svc->createPaymentLink(['amount' => '10.00', 'currency' => 'USD']);
    expect($res['success'])->toBeTrue();
    expect($res['data']['url'])->toBe('https://link.example');
});

it('creates and confirms payment intent', function () {
    seedAirwallexConfig();

    Http::fake([
        'https://api-demo.airwallex.com/api/v1/authentication/login'            => Http::response(['token' => 't123'], 200),
        'https://api-demo.airwallex.com/api/v1/pa/payment_intents/create'       => Http::response(['id' => 'pi_1'], 200),
        'https://api-demo.airwallex.com/api/v1/pa/payment_intents/pi_1/confirm' => Http::response(['status' => 'succeeded'], 200),
    ]);

    $svc = new AirwallexService;
    $create = $svc->createPaymentIntent(['amount' => '10.00', 'currency' => 'USD']);
    expect($create['success'])->toBeTrue();

    $confirm = $svc->confirmPaymentIntent('pi_1', ['payment_method' => ['type' => 'card']]);
    expect($confirm['success'])->toBeTrue();
    expect($confirm['data']['status'])->toBe('succeeded');
});

it('creates refund', function () {
    seedAirwallexConfig();

    Http::fake([
        'https://api-demo.airwallex.com/api/v1/authentication/login' => Http::response(['token' => 't123'], 200),
        'https://api-demo.airwallex.com/api/v1/pa/refunds/create'    => Http::response(['id' => 'rf_1', 'status' => 'succeeded'], 200),
    ]);

    $svc = new AirwallexService;
    $res = $svc->createRefund(['payment_intent_id' => 'pi_1', 'amount' => '10.00', 'currency' => 'USD']);
    expect($res['success'])->toBeTrue();
    expect($res['data']['status'])->toBe('succeeded');
});

it('verifies webhook signature', function () {
    $nonce = '1650458086181.somenonce';
    $secret = 'shared_secret';
    $signature = base64_encode(hash_hmac('sha256', $nonce, $secret, true));

    $svc = new AirwallexService;
    expect($svc->verifyWebhookSignature($nonce, $signature, $secret))->toBeTrue();
});
