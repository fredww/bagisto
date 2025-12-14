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
        $debug = (bool) (core()->getConfigData('sales.payment_methods.airwallex.debug') ?? env('AIRWALLEX_DEBUG', false));

        if ($sandbox) {
            $baseUrl = $baseUrl ?: 'https://api-demo.airwallex.com';
        } else {
            $baseUrl = $baseUrl ?: 'https://api.airwallex.com';
        }

        return compact('sandbox', 'baseUrl', 'clientId', 'apiKey', 'merchantId', 'callbackUrl', 'webhookSecret', 'paymentMethods', 'debug');
    }

    public function obtainAccessToken(): ?string
    {
        $cfg = $this->getConfig();

        try {
            $loginUrl = rtrim($cfg['baseUrl'], '/').'/api/v1/authentication/login';
            $this->debugLog('login_request', [
                'url'     => $loginUrl,
                'headers' => ['x-api-key' => $cfg['apiKey'], 'x-client-id' => $cfg['clientId']],
            ]);
            $resp = Http::timeout(10)
                ->withHeaders([
                    'x-api-key'   => $cfg['apiKey'],
                    'x-client-id' => $cfg['clientId'],
                ])
                ->post($loginUrl);

            if (! $resp->successful()) {
                $this->debugLog('login_response', ['status' => $resp->status(), 'body' => $resp->body()]);
                Log::warning('Airwallex login HTTP error', ['status' => $resp->status(), 'body' => $resp->body()]);

                return null;
            }

            $this->debugLog('login_response', ['status' => $resp->status(), 'body' => $resp->body()]);
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

        if (! array_key_exists('request_id', $payload)) {
            $orderNo = (string) ($payload['merchant_order_id'] ?? 'order');
            $payload['request_id'] = 'pl-'.$orderNo.'-'.bin2hex(random_bytes(6));
        }

        if (! array_key_exists('title', $payload)) {
            $payload['title'] = 'Order '.((string) ($payload['merchant_order_id'] ?? 'Order'));
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
            $this->debugLog('payment_link_request', ['url' => $url, 'payload' => $payload]);
            $resp = Http::timeout(12)
                ->withToken($token)
                ->asJson()
                ->post($url, $payload);

            if (! $resp->successful()) {
                $this->debugLog('payment_link_response', ['status' => $resp->status(), 'body' => $resp->body()]);
                return ['success' => false, 'status' => $resp->status(), 'msg' => 'http_error', 'body' => $resp->body()];
            }

            $json = $resp->json();

            $this->debugLog('payment_link_response', ['status' => $resp->status(), 'body' => $resp->body()]);
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

        if (! array_key_exists('request_id', $payload)) {
            $orderNo = (string) ($payload['merchant_order_id'] ?? 'order');
            $payload['request_id'] = 'pi-'.$orderNo.'-'.bin2hex(random_bytes(6));
        }

        $base = rtrim($cfg['baseUrl'], '/');
        $primary = $base.'/api/v1/pa/payment_intents/create';
        $fallback = $base.'/api/v1/pa/payment_intents';

        try {
            $this->debugLog('payment_intent_request', ['url' => $primary, 'payload' => $payload]);
            $resp = Http::timeout(12)->withToken($token)->asJson()->post($primary, $payload);
            $this->debugLog('payment_intent_response', ['status' => $resp->status(), 'body' => $resp->body()]);
            if ($resp->status() === 404) {
                $this->debugLog('payment_intent_fallback_request', ['url' => $fallback, 'payload' => $payload]);
                $resp = Http::timeout(12)->withToken($token)->asJson()->post($fallback, $payload);
                $this->debugLog('payment_intent_fallback_response', ['status' => $resp->status(), 'body' => $resp->body()]);
            }

            if (! $resp->successful()) {
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
            $this->debugLog('confirm_intent_request', ['url' => $url, 'payload' => $payload, 'intent_id' => $intentId]);
            $resp = Http::timeout(12)->withToken($token)->asJson()->post($url, $payload);
            if (! $resp->successful()) {
                $this->debugLog('confirm_intent_response', ['status' => $resp->status(), 'body' => $resp->body(), 'intent_id' => $intentId]);
                return ['success' => false, 'status' => $resp->status(), 'msg' => 'http_error', 'body' => $resp->body()];
            }

            $this->debugLog('confirm_intent_response', ['status' => $resp->status(), 'body' => $resp->body(), 'intent_id' => $intentId]);
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
            $this->debugLog('get_intent_request', ['url' => $url, 'intent_id' => $intentId]);
            $resp = Http::timeout(10)->withToken($token)->get($url);
            if (! $resp->successful()) {
                $this->debugLog('get_intent_response', ['status' => $resp->status(), 'body' => $resp->body(), 'intent_id' => $intentId]);
                return ['success' => false, 'status' => $resp->status(), 'msg' => 'http_error'];
            }

            $this->debugLog('get_intent_response', ['status' => $resp->status(), 'body' => $resp->body(), 'intent_id' => $intentId]);
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
            $this->debugLog('refund_request', ['url' => $primary, 'payload' => $payload]);
            $resp = Http::timeout(12)->withToken($token)->asJson()->post($primary, $payload);
            $this->debugLog('refund_response', ['status' => $resp->status(), 'body' => $resp->body()]);
            if ($resp->status() === 404) {
                $this->debugLog('refund_fallback_request', ['url' => $fallback, 'payload' => $payload]);
                $resp = Http::timeout(12)->withToken($token)->asJson()->post($fallback, $payload);
                $this->debugLog('refund_fallback_response', ['status' => $resp->status(), 'body' => $resp->body()]);
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

    public function verifyWebhookSignature(string $timestamp, string $signature, string $sharedSecret, ?string $body = null): bool
    {
        $rawBody = (string) ($body ?? '');
        $valueToDigest = $timestamp.$rawBody;

        $macHex = hash_hmac('sha256', $valueToDigest, $sharedSecret, false);
        if (hash_equals($macHex, $signature)) {
            return true;
        }

        $macRaw = hash_hmac('sha256', $valueToDigest, $sharedSecret, true);
        $expectedB64 = base64_encode($macRaw);
        if (hash_equals($expectedB64, $signature)) {
            return true;
        }

        return false;
    }

    protected function debugLog(string $label, array $data): void
    {
        $cfg = $this->getConfig();
        if (empty($cfg['debug'])) {
            return;
        }
        $path = storage_path('logs/airwallex.log');
        $line = '['.date('c').'] '.$label.' '.json_encode($this->maskArray($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @file_put_contents($path, $line.PHP_EOL, FILE_APPEND);
    }

    protected function maskArray($value)
    {
        $keys = ['x-api-key', 'x-client-id', 'authorization', 'client_secret', 'api_key', 'client_id', 'token', 'access_token'];
        if (is_array($value)) {
            $masked = [];
            foreach ($value as $k => $v) {
                if (is_string($k) && in_array(strtolower($k), $keys, true)) {
                    $masked[$k] = '***';
                } else {
                    $masked[$k] = $this->maskArray($v);
                }
            }
            return $masked;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return json_encode($this->maskArray($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            foreach ($keys as $k) {
                $value = preg_replace('/("'.$k.'"\s*:\s*")([^"]+)"/i', '$1***"', $value);
            }
            return $value;
        }
        return $value;
    }
}
