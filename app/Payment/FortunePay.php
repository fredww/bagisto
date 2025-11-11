<?php

namespace App\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

/**
 * 方法说明（中文）：Bagisto 支付方法类，用于在前端展示 FortunePay 并提供重定向 URL
 * Purpose (English): Bagisto payment method class to expose FortunePay on frontend and provide redirect URL
 */
class FortunePay extends Payment
{
    /**
     * 支付方法代码
     * Payment method code
     *
     * @var string
     */
    protected $code = 'fortune_pay';

    /**
     * 获取支付网关重定向URL
     * Get payment gateway redirect URL
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return route('fortune.redirect');
    }

    /**
     * 检查支付方法是否可用
     * Check if payment method is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        // Active + essential credentials present
        return (bool) $this->getConfigData('active')
            && (bool) $this->getConfigData('merchant_id')
            && (bool) $this->getConfigData('user_key')
            && (bool) $this->getConfigData('base_url');
    }

    /**
     * 获取支付方法图标
     * Get payment method image
     *
     * @return string
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/money-transfer.png', 'shop');
    }
}