<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * 方法说明（中文）：封装 Asiabill PHP SDK 的服务类，负责初始化与请求转发
 * Purpose (English): Service wrapper for Asiabill PHP SDK, handles initialization and request proxy
 */
class AsiabillService
{
    /**
     * 读取配置（来源：Bagisto 后台配置 sales.payment_methods.asiabill.*）
     * Read config from Bagisto admin settings
     */
    public function getConfig(): array
    {
        return [
            'mode'        => (string) (core()->getConfigData('sales.payment_methods.asiabill.mode') ?: 'test'),
            'gateway_no'  => (string) (core()->getConfigData('sales.payment_methods.asiabill.gateway_no') ?: ''),
            'sign_key'    => (string) (core()->getConfigData('sales.payment_methods.asiabill.sign_key') ?: ''),
            'callbackUrl' => (string) (core()->getConfigData('sales.payment_methods.asiabill.callback_url') ?: route('asiabill.notify')),
            'returnUrl'   => (string) (core()->getConfigData('sales.payment_methods.asiabill.return_url') ?: route('asiabill.return')),
            'debug_log'   => (bool) (core()->getConfigData('sales.payment_methods.asiabill.debug_log') ?: false),
        ];
    }

    /**
     * 初始化 SDK 实例
     * Initialize SDK instance
     */
    public function makeIntegration(): \Asiabill\Classes\AsiabillIntegration
    {
        $cfg = $this->getConfig();
        $asiabill = new \Asiabill\Classes\AsiabillIntegration(
            $cfg['mode'],
            $cfg['gateway_no'],
            $cfg['sign_key']
        );

        if ($cfg['debug_log']) {
            try {
                $asiabill->startLogger(true, storage_path('logs/asiabill'));
            } catch (\Throwable $e) {
                Log::warning('Asiabill startLogger failed', ['e' => $e->getMessage()]);
            }
        }

        return $asiabill;
    }

    /**
     * 获取 JS SDK 脚本地址
     * Get JS SDK script tag or URL
     */
    public function getJsScript(): string
    {
        $asiabill = $this->makeIntegration();

        return (string) $asiabill->getJsScript();
    }

    /**
     * 获取 sessionToken
     * Get sessionToken
     */
    public function getSessionToken(): array
    {
        $asiabill = $this->makeIntegration();

        return (array) $asiabill->request('sessionToken');
    }

    /**
     * 创建或获取客户ID（基于订单账单信息）
     * Create or fetch customerId using billing info
     */
    public function ensureCustomerId(array $billing): ?string
    {
        $asiabill = $this->makeIntegration();

        $payload = [
            'body' => [
                'email'      => (string) Arr::get($billing, 'email', ''),
                'firstName'  => (string) Arr::get($billing, 'first_name', ''),
                'lastName'   => (string) Arr::get($billing, 'last_name', ''),
                'phone'      => (string) Arr::get($billing, 'phone', ''),
                'address'    => [
                    'line1'      => (string) Arr::get($billing, 'address', ''),
                    'line2'      => '',
                    'city'       => (string) Arr::get($billing, 'city', ''),
                    'country'    => (string) Arr::get($billing, 'country', ''),
                    'state'      => (string) Arr::get($billing, 'state', ''),
                    'postalCode' => (string) Arr::get($billing, 'postcode', ''),
                ],
            ],
        ];

        try {
            $resp = (array) $asiabill->request('customers', $payload);
            $code = Arr::get($resp, 'code');
            if ($code === '0000' || $code === '00000') {
                $data = Arr::get($resp, 'data', []);

                return (string) Arr::get($data, 'customerId');
            }
        } catch (\Throwable $e) {
            Log::warning('Asiabill ensureCustomerId failed', ['e' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * 发起扣款
     * Confirm charge
     */
    public function confirmCharge(array $data): array
    {
        $asiabill = $this->makeIntegration();

        return (array) $asiabill->request('confirmCharge', $data);
    }
}
