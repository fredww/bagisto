<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

function seedAirwallexConfigBasic(): void
{
    putenv('AIRWALLEX_CLIENT_ID=client_abc');
    putenv('AIRWALLEX_API_KEY=key_xyz');
    putenv('AIRWALLEX_BASE_URL=https://api-demo.airwallex.com');
    putenv('AIRWALLEX_SANDBOX=true');
    Config::set('services.airwallex.client_id', 'client_abc');
    Config::set('services.airwallex.api_key', 'key_xyz');
}

it('returns payment intent status', function () {
    seedAirwallexConfigBasic();

    Http::fake([
        'https://api-demo.airwallex.com/api/v1/authentication/login' => Http::response(['token' => 't123'], 200),
        'https://api-demo.airwallex.com/api/v1/pa/payment_intents/pi_1' => Http::response(['id' => 'pi_1', 'status' => 'succeeded'], 200),
    ]);

    $resp = $this->get('/airwallex/status/pi_1');
    $resp->assertStatus(200);
    $resp->assertJson(['id' => 'pi_1', 'status' => 'succeeded']);
});

it('creates refund via route', function () {
    seedAirwallexConfigBasic();

    Http::fake([
        'https://api-demo.airwallex.com/api/v1/authentication/login' => Http::response(['token' => 't123'], 200),
        'https://api-demo.airwallex.com/api/v1/pa/refunds/create' => Http::response(['id' => 'rf_1', 'status' => 'succeeded'], 200),
    ]);

    $resp = $this->postJson('/airwallex/refund', [
        'payment_intent_id' => 'pi_1',
        'amount' => 10.00,
        'currency' => 'USD',
    ]);

    $resp->assertStatus(200);
    $resp->assertJson(['id' => 'rf_1', 'status' => 'succeeded']);
});
