<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function seedAirwallexConfigBasic(): void
{
    putenv('AIRWALLEX_CLIENT_ID=client_abc');
    putenv('AIRWALLEX_API_KEY=key_xyz');
    putenv('AIRWALLEX_BASE_URL=https://api-demo.airwallex.com');
    putenv('AIRWALLEX_SANDBOX=true');
    Config::set('services.airwallex.client_id', 'client_abc');
    Config::set('services.airwallex.api_key', 'key_xyz');
}

it('accepts valid webhook with timestamp signature', function () {
    seedAirwallexConfigBasic();
    putenv('AIRWALLEX_WEBHOOK_SECRET=shared_secret');
    putenv('AIRWALLEX_WEBHOOK_TOLERANCE_SECONDS=600');

    $ts = (string) time();
    $payload = ['type' => 'payment_intent.pending'];
    $body = json_encode($payload);
    $sig = hash_hmac('sha256', $ts.$body, 'shared_secret');

    $resp = $this->postJson('/airwallex/webhook', $payload, [
        'x-timestamp'  => $ts,
        'x-signature'  => $sig,
    ]);

    $resp->assertStatus(200);
});

it('accepts valid webhook with millisecond timestamp signature', function () {
    seedAirwallexConfigBasic();
    putenv('AIRWALLEX_WEBHOOK_SECRET=shared_secret');
    putenv('AIRWALLEX_WEBHOOK_TOLERANCE_SECONDS=600');

    $tsMs = (string) (int) floor(microtime(true) * 1000);
    $payload = ['type' => 'payment_intent.pending'];
    $body = json_encode($payload);
    $sig = hash_hmac('sha256', $tsMs.$body, 'shared_secret');

    $resp = $this->postJson('/airwallex/webhook', $payload, [
        'x-timestamp'  => $tsMs,
        'x-signature'  => $sig,
    ]);

    $resp->assertStatus(200);
});

it('rejects webhook when timestamp outside tolerance', function () {
    seedAirwallexConfigBasic();
    putenv('AIRWALLEX_WEBHOOK_SECRET=shared_secret');
    putenv('AIRWALLEX_WEBHOOK_TOLERANCE_SECONDS=60');

    $ts = (string) (time() - 3600);
    $payload = ['type' => 'payment_intent.pending'];
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $sig = base64_encode(hash_hmac('sha256', $ts.$body, 'shared_secret', true));

    $resp = $this->postJson('/airwallex/webhook', $payload, [
        'x-timestamp'  => $ts,
        'x-signature'  => $sig,
    ]);

    $resp->assertStatus(401);
});

it('returns payment intent status', function () {
    seedAirwallexConfigBasic();

    Http::fake([
        'https://api-demo.airwallex.com/api/v1/authentication/login'    => Http::response(['token' => 't123'], 200),
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
        'https://api-demo.airwallex.com/api/v1/pa/refunds/create'    => Http::response(['id' => 'rf_1', 'status' => 'succeeded'], 200),
    ]);

    $resp = $this->postJson('/airwallex/refund', [
        'payment_intent_id' => 'pi_1',
        'amount'            => 10.00,
        'currency'          => 'USD',
    ]);

    $resp->assertStatus(200);
    $resp->assertJson(['id' => 'rf_1', 'status' => 'succeeded']);
});
