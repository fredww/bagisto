<?php

namespace Webkul\Airwallex\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirwallexService
{
    public function getConfig(): array
    {
        $sandbox = (bool) (core()->getConfigData('sales.payment_methods.airwallex.sandbox') ?? env('AIRWALLEX_SANDBOX', true));
        $baseUrl = (string) (core()->getConfigData('sales.payment_methods.airwallex.base_url') ?? env('AIRWALLEX_BASE_URL', ''));
        $clientId = (string) (core()->getConfigData('sales.payment_methods.airwallex.client_id') ?? env('AIRWALLEX_CLIENT_ID', config('services.airwallex.client_id')) ?? '');
        $apiKey = (string) (core()->getConfigData('sales.payment_methods.airwallex.api_key') ?? env('AIRWALLEX_API_KEY', config('services.airwallex.api_key')) ?? '');
        $merchantId = (string) (core()->getConfigData('sales.payment_methods.airwallex.merchant_id') ?? env('AIRWALLEX_MERCHANT_ID', ''));
        $callbackUrl = (string) (core()->getConfigData('sales.payment_methods.airwallex.callback_url') ?? env('AIRWALLEX_CALLBACK_URL', ''));
        $webhookSecret = (string) (core()->getConfigData('sales.payment_methods.airwallex.webhook_secret') ?? env('AIRWALLEX_WEBHOOK_SECRET', ''));
        $paymentMethods = (string) (core()->getConfigData('sales.payment_methods.airwallex.methods') ?? env('AIRWALLEX_PAYMENT_METHODS', ''));

        if ($sandbox) {
            $baseUrl = $baseUrl ?: 'https://api-demo.airwallex.com';
        } else {
            $baseUrl = $baseUrl ?: 'https://api.airwallex.com';
        }

        return compact('sandbox', 'baseUrl', 'clientId', 'apiKey', 'merchantId', 'callbackUrl', 'webhookSecret', 'paymentMethods');
    }

    public function obtainAccessToken(): ?string
    {
        $cfg = $this->getConfig();

        try {
            $resp = Http::timeout(10)
                ->withHeaders([
                    'x-api-key'   => $cfg['apiKey'],
                    'x-client-id' => $cfg['clientId'],
                ])
                ->post(rtrim($cfg['baseUrl'], '/').'/api/v1/authentication/login');

            if (! $resp->successful()) {
                Log::warning('Airwallex login HTTP error', ['status' => $resp->status(), 'body' => $resp->body()]);

                return null;
            }

            return (string) Arr::get($resp->json(), 'token');
        } catch (\Throwable $e) {
            Log::error('Airwallex obtainAccessToken exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function createPaymentLink(array $payload): array
    {
        $cfg = $this->getConfig();
        $token = $this->obtainAccessToken();
        if (! $token) {
            return ['success' => false, 'msg' => 'auth_failed'];
        }

        if (! array_key_exists('title', $payload)) {
            $payload['title'] = 'Order '.$payload['merchant_order_id'] ?? 'Order';
        }

        if (! array_key_exists('reusable', $payload)) {
            $payload['reusable'] = false;
        }

        if (empty($payload['return_url']) && ! empty($cfg['callbackUrl'])) {
            $payload['return_url'] = (string) $cfg['callbackUrl'];
        }

        $methods = array_values(array_filter(array_map('trim', explode(',', (string) $cfg['paymentMethods']))));
        if (! empty($methods)) {
            $payload['payment_method_types'] = $methods;
        }

        $url = rtrim($cfg['baseUrl'], '/').'/api/v1/pa/payment_links/create';

        try {
            $resp = Http::timeout(12)
                ->withToken($token)
                ->asJson()
                ->post($url, $payload);

            if (! $resp->successful()) {
                return ['success' => false, 'status' => $resp->status(), 'msg' => 'http_error', 'body' => $resp->body()];
            }

            $json = $resp->json();

            return ['success' => true, 'data' => $json];
        } catch (\Throwable $e) {
            Log::error('Airwallex createPaymentLink exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'msg' => 'exception'];
        }
    }

    public function createPaymentIntent(array $payload): array
    {
        $cfg = $this->getConfig();
        $token = $this->obtainAccessToken();
        if (! $token) {
            return ['success' => false, 'msg' => 'auth_failed'];
        }

        $base = rtrim($cfg['baseUrl'], '/');
        $primary = $base.'/api/v1/pa/payment_intents/create';
        $fallback = $base.'/api/v1/pa/payment_intents';

        try {
            $resp = Http::timeout(12)->withToken($token)->asJson()->post($primary, $payload);
            if ($resp->status() === 404) {
                $resp = Http::timeout(12)->withToken($token)->asJson()->post($fallback, $payload);
            }

            if (! $resp->ok()) {
                return ['success' => false, 'status' => $resp->status(), 'msg' => 'http_error', 'body' => $resp->body()];
            }

            return ['success' => true, 'data' => $resp->json()];
        } catch (\Throwable $e) {
            Log::error('Airwallex createPaymentIntent exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'msg' => 'exception'];
        }
    }

    public function confirmPaymentIntent(string $intentId, array $payload): array
    {
        $cfg = $this->getConfig();
        $token = $this->obtainAccessToken();
        if (! $token) {
            return ['success' => false, 'msg' => 'auth_failed'];
        }

        $url = rtrim($cfg['baseUrl'], '/').'/api/v1/pa/payment_intents/'.$intentId.'/confirm';

        try {
            $resp = Http::timeout(12)->withToken($token)->asJson()->post($url, $payload);
            if (! $resp->successful()) {
                return ['success' => false, 'status' => $resp->status(), 'msg' => 'http_error', 'body' => $resp->body()];
            }

            return ['success' => true, 'data' => $resp->json()];
        } catch (\Throwable $e) {
            Log::error('Airwallex confirmPaymentIntent exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'msg' => 'exception'];
        }
    }

    public function getPaymentIntent(string $intentId): array
    {
        $cfg = $this->getConfig();
        $token = $this->obtainAccessToken();
        if (! $token) {
            return ['success' => false, 'msg' => 'auth_failed'];
        }

        $url = rtrim($cfg['baseUrl'], '/').'/api/v1/pa/payment_intents/'.$intentId;

        try {
            $resp = Http::timeout(10)->withToken($token)->get($url);
            if (! $resp->successful()) {
                return ['success' => false, 'status' => $resp->status(), 'msg' => 'http_error'];
            }

            return ['success' => true, 'data' => $resp->json()];
        } catch (\Throwable $e) {
            Log::error('Airwallex getPaymentIntent exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'msg' => 'exception'];
        }
    }

    public function createRefund(array $payload): array
    {
        $cfg = $this->getConfig();
        $token = $this->obtainAccessToken();
        if (! $token) {
            return ['success' => false, 'msg' => 'auth_failed'];
        }

        $base = rtrim($cfg['baseUrl'], '/');
        $primary = $base.'/api/v1/pa/refunds/create';
        $fallback = $base.'/api/v1/pa/refunds';

        try {
            $resp = Http::timeout(12)->withToken($token)->asJson()->post($primary, $payload);
            if ($resp->status() === 404) {
                $resp = Http::timeout(12)->withToken($token)->asJson()->post($fallback, $payload);
            }

            if (! $resp->successful()) {
                return ['success' => false, 'status' => $resp->status(), 'msg' => 'http_error', 'body' => $resp->body()];
            }

            return ['success' => true, 'data' => $resp->json()];
        } catch (\Throwable $e) {
            Log::error('Airwallex createRefund exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'msg' => 'exception'];
        }
    }

    public function verifyWebhookSignature(string $nonce, string $signature, string $sharedSecret): bool
    {
        $mac = hash_hmac('sha256', $nonce, $sharedSecret, true);
        $expected = base64_encode($mac);

        return hash_equals($expected, $signature);
    }
}
