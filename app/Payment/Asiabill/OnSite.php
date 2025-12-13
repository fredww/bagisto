<?php

namespace App\Payment\Asiabill;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

/**
 * 方法说明（中文）：Asiabill 站内支付方法类，提供前端展示与重定向至站内支付页面
 * Purpose (English): Asiabill on-site payment method class to expose in checkout and redirect to on-site page
 */
class OnSite extends Payment
{
    /**
     * 支付方法代码
     * Payment method code
     *
     * @var string
     */
    protected $code = 'asiabill';

    /**
     * 获取站内支付页面重定向URL
     * Get redirect URL to on-site payment page
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return route('asiabill.onsite');
    }

    /**
     * 检查支付方法是否可用（需启用且配置完整）
     * Check if payment method is available (enabled and properly configured)
     *
     * @return bool
     */
    public function isAvailable()
    {
        return (bool) $this->getConfigData('active')
            && (bool) $this->getConfigData('gateway_no')
            && (bool) $this->getConfigData('sign_key')
            && in_array((string) $this->getConfigData('mode'), ['test', 'live']);
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
