<?php

namespace App\Services;

use App\Models\FortunePayment;
use App\Models\FortuneSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FortunePayService
{
    /**
     * 获取当前有效配置（优先数据库，其次env/config）
     * Purpose: Fetch active FortunePay configuration from DB or fallback to config/env
     */
    public function getConfig(): array
    {
        $setting = FortuneSetting::query()->latest('id')->first();

        return [
            'base_url' => $setting->base_url ?: (string) config('fortune.base_url'),
            'merchant_no' => $setting->merchant_no ?: (string) config('fortune.merchant_no'),
            'user_key' => $setting->getUserKey(),
            'username' => $setting->username ?: (string) config('fortune.username'),
            'bn' => $setting->bn ?: (string) config('fortune.bn'),
            'payment_method' => $setting->payment_method ?: (string) config('fortune.payment_method'),
            'notify_url' => $setting->notify_url ?: (string) config('fortune.notify_url'),
            'success_uri' => $setting->success_uri ?: (string) config('fortune.success_uri'),
            'return_uri' => $setting->return_uri ?: (string) config('fortune.return_uri'),
            'channel_redirect' => (bool) ($setting->channel_redirect ?? config('fortune.channel_redirect')),
            'channel_iframe' => (bool) ($setting->channel_iframe ?? config('fortune.channel_iframe')),
        ];
    }

    /**
     * 生成下单签名
     * Purpose: Sign for create_payment using sha256(invoice_id + order_no + USER_KEY)
     */
    public function signCreate(string $invoiceId, string $orderNo, string $userKey): string
    {
        $hashSrc = $invoiceId . $orderNo . $userKey;
        return strtoupper(hash('sha256', $hashSrc));
    }

    /**
     * 生成返回/通知签名
     * Purpose: Sign for return/notify using sha256(failure_code + invoice_id + order_no + USER_KEY)
     */
    public function signReturn(string $failureCode, string $invoiceId, string $orderNo, string $userKey): string
    {
        $hashSrc = $failureCode . $invoiceId . $orderNo . $userKey;
        return strtoupper(hash('sha256', $hashSrc));
    }

    /**
     * 构建下单请求载荷
     * Purpose: Compose payload according to the gateway spec
     */
    public function buildPayload(array $data, array $config): array
    {
        // Collect client data
        $clientIp = $data['client_ip'] ?? request()->ip();
        $clientAgent = $data['client_agent'] ?? (string) request()->header('User-Agent');
        $clientLang = $data['client_language'] ?? (string) request()->header('Accept-Language');

        // Ensure URIs present; fallback to app routes if missing
        $notifyUrl = $config['notify_url'] ?: route('fortune.notify');
        $successUri = $config['success_uri'] ?: route('fortune.return');
        $returnUri = $config['return_uri'] ?: route('fortune.return');

        $invoiceId = (string) $data['invoice_id'];
        $orderNo = (string) $data['order_no'];
        $token = $this->signCreate($invoiceId, $orderNo, $config['user_key']);

        return [
            'client_ip' => $clientIp,
            'client_agent' => $clientAgent,
            'client_language' => $clientLang,
            'order_no' => $orderNo,
            'invoice_id' => $invoiceId,
            'currency' => (string) $data['currency'],
            'subject' => (string) ($data['subject'] ?? ''),
            'body' => (string) ($data['body'] ?? ''),
            'success_uri' => $successUri,
            'return_uri' => $returnUri,
            'notify_url' => $notifyUrl,
            'first_name' => (string) ($data['first_name'] ?? ''),
            'last_name' => (string) ($data['last_name'] ?? ''),
            'email' => (string) ($data['email'] ?? ''),
            'telephone' => (string) ($data['telephone'] ?? ''),
            'address' => (string) ($data['address'] ?? ''),
            'city' => (string) ($data['city'] ?? ''),
            'country' => (string) ($data['country'] ?? ''),
            'zip_code' => (string) ($data['zip_code'] ?? ''),
            'zone' => (string) ($data['zone'] ?? ''),
            'amount' => (string) $data['amount'],
            'payment_method' => $config['payment_method'],
            'bn' => $config['bn'],
            'username' => $config['username'],
            'token' => $token,
        ];
    }

    /**
     * 发起下单请求
     * Purpose: Call gateway create_payment and persist initial record
     */
    public function createPayment(array $data): array
    {
        $config = $this->getConfig();

        // Minimal required fields validation
        foreach (['order_no', 'invoice_id', 'currency', 'amount'] as $required) {
            if (!Arr::has($data, $required)) {
                return [
                    'code' => 422,
                    'result' => [
                        'success' => false,
                        'msg' => "Missing required field: {$required}",
                    ],
                    'msg' => 'Invalid payload',
                ];
            }
        }

        $payload = $this->buildPayload($data, $config);

        // Persist request intent
        $payment = FortunePayment::create([
            'order_no' => (string) $payload['order_no'],
            'invoice_id' => (string) $payload['invoice_id'],
            'currency' => (string) $payload['currency'],
            'amount' => (float) $payload['amount'],
            'status' => 'requested',
            'client_ip' => (string) $payload['client_ip'],
            'client_agent' => (string) $payload['client_agent'],
            'client_language' => (string) $payload['client_language'],
            'bn' => (string) $payload['bn'],
            'payment_method' => (string) $payload['payment_method'],
        ]);

        try {
            $url = rtrim($config['base_url'], '/') . '/pay/create_payment';
            $resp = Http::timeout(10)->retry(3, 500)->asJson()->post($url, $payload);

            if (!$resp->ok()) {
                Log::warning('FortunePay create_payment HTTP error', ['status' => $resp->status(), 'body' => $resp->body()]);
                $payment->update(['status' => 'pending']);
                return [
                    'code' => $resp->status(),
                    'result' => [
                        'success' => false,
                        'msg' => 'Gateway unreachable or error',
                    ],
                    'msg' => 'HTTP error',
                ];
            }

            $json = $resp->json();
            $result = Arr::get($json, 'result', []);

            $payment->update([
                'gateway_response' => $json,
                'redirect' => (bool) Arr::get($result, 'redirect', true),
                'url' => (string) Arr::get($result, 'url'),
                'pay_no' => (string) Arr::get($result, 'pay_no'),
                'status' => Arr::get($result, 'success') ? 'pending' : 'failed',
            ]);

            return [
                'code' => (int) Arr::get($json, 'code', 200),
                'result' => [
                    'success' => (bool) Arr::get($result, 'success', false),
                    'msg' => Arr::get($result, 'msg'),
                    'redirect' => (bool) Arr::get($result, 'redirect', true),
                    'url' => Arr::get($result, 'url'),
                    'pay_no' => Arr::get($result, 'pay_no'),
                ],
                'msg' => Arr::get($json, 'msg'),
            ];
        } catch (\Throwable $e) {
            Log::error('FortunePay create_payment exception', ['e' => $e->getMessage()]);
            $payment->update(['status' => 'pending']);
            return [
                'code' => 500,
                'result' => [
                    'success' => false,
                    'msg' => 'Internal error',
                ],
                'msg' => 'Exception during gateway call',
            ];
        }
    }

    /**
     * 验证返回/通知签名
     * Purpose: Verify token for return/notify
     */
    public function verifyToken(array $params): bool
    {
        $config = $this->getConfig();
        $expected = $this->signReturn(
            (string) ($params['failure_code'] ?? ''),
            (string) ($params['invoice_id'] ?? ''),
            (string) ($params['order_no'] ?? ''),
            (string) $config['user_key']
        );

        return strtoupper((string) ($params['token'] ?? '')) === $expected;
    }

    /**
     * 更新支付状态
     * Purpose: Update payment record status and metadata
     */
    public function updatePaymentStatus(string $orderNo, string $invoiceId, string $status, array $extra = []): void
    {
        $payment = FortunePayment::where('order_no', $orderNo)->where('invoice_id', $invoiceId)->first();
        if (!$payment) {
            return;
        }

        $update = array_merge(['status' => $status], $extra);
        $payment->update($update);
    }

    /**
     * 查询支付状态（本地）
     * Purpose: Query payment status locally by order_no or invoice_id
     */
    public function queryLocal(?string $orderNo = null, ?string $invoiceId = null): ?FortunePayment
    {
        $q = FortunePayment::query();
        if ($orderNo) {
            $q->where('order_no', $orderNo);
        }
        if ($invoiceId) {
            $q->where('invoice_id', $invoiceId);
        }
        return $q->latest('id')->first();
    }
}