<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Core\Models\CoreConfig;

uses(RefreshDatabase::class);

function seedAirwallexConfigBasic(): void
{
    CoreConfig::factory()->create([
        'code' => 'sales.payment_methods.airwallex.active',
        'value' => 1,
    ]);
    CoreConfig::factory()->create([
        'code' => 'sales.payment_methods.airwallex.client_id',
        'value' => 'client_abc',
    ]);
    CoreConfig::factory()->create([
        'code' => 'sales.payment_methods.airwallex.api_key',
        'value' => 'key_xyz',
    ]);
    CoreConfig::factory()->create([
        'code' => 'sales.payment_methods.airwallex.base_url',
        'value' => 'https://api-demo.airwallex.com',
    ]);
    CoreConfig::factory()->create([
        'code' => 'sales.payment_methods.airwallex.sandbox',
        'value' => 1,
    ]);
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

