<?php

namespace App\Services;

use App\Models\FortunePayment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FortunePayService
{
    /**
     * 统一配置读取（仅 Bagisto 管理后台）
     * Purpose: Read FortunePay config exclusively from admin settings (CoreConfig)
     */
    public function getConfig(): array
    {
        // Read all fields from Bagisto admin panel: sales.payment_methods.fortune_pay.*
        $baseUrl       = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.base_url') ?? '');
        $userKey       = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.user_key') ?? '');
        $username      = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.username') ?? '');
        $bn            = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.bn') ?? '');
        $paymentMethod = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.payment_method') ?? '');
        $notifyUrl     = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.notify_url') ?? '');
        $successUri    = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.success_uri') ?? '');
        $returnUri     = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.return_uri') ?? '');
        $uiMethod      = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.method') ?? ''); // 'redirect_pay' | 'iframe'
        $debugLog      = (bool)  (core()->getConfigData('sales.payment_methods.fortune_pay.debug_log') ?? false);

        // UI flags derived strictly from admin value
        $channelRedirect = $uiMethod === 'redirect_pay';
        $channelIframe   = $uiMethod === 'iframe';

        // Optional: map merchant_id for compatibility naming (merchant_no)
        $merchantId = (string) (core()->getConfigData('sales.payment_methods.fortune_pay.merchant_id') ?? '');

        return [
            'base_url'        => $baseUrl,
            'merchant_no'     => $merchantId,
            'user_key'        => $userKey,
            'username'        => $username,
            'bn'              => $bn,
            'payment_method'  => $paymentMethod,
            'notify_url'      => $notifyUrl,
            'success_uri'     => $successUri,
            'return_uri'      => $returnUri,
            'channel_redirect'=> $channelRedirect,
            'channel_iframe'  => $channelIframe,
            'debug_log'       => $debugLog,
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
            'address' => trim((string) ($data['address'] ?? '')),
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

        // Build endpoint URL outside try to allow logging before request
        $url = rtrim($config['base_url'], '/') . '/pay/create_payment';

        // Debug: log outgoing payload when enabled
        if (!empty($config['debug_log'])) {
            // Internal logic: keep concise and avoid sensitive keys if any
            Log::channel('fortune')->info('FortunePay debug: create_payment outgoing payload', [
                'url'     => $url,
                'payload' => $payload,
            ]);
        }

        try {
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

            // Debug: log gateway response JSON
            if (!empty($config['debug_log'])) {
                Log::channel('fortune')->info('FortunePay debug: create_payment response', [
                    'status' => $resp->status(),
                    'json'   => $json,
                ]);
            }
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
            Log::channel('fortune')->error('FortunePay create_payment exception', ['e' => $e->getMessage()]);

            // Debug: include stack trace when enabled
            if (!empty($config['debug_log'])) {
                Log::channel('fortune')->error('FortunePay debug: create_payment exception trace', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
            }
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