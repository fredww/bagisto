<?php

namespace Webkul\SecurityPayment\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;
use Webkul\Checkout\Facades\Cart;

/**
 * 安全支付网关类 - 从Magento 1.8迁移
 * Security Payment Gateway Class - Migrated from Magento 1.8
 */
class SecurityPayment extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'security_payment';

    /**
     * Payment method version.
     *
     * @var string
     */
    protected $version = 'V7.0-A-100';

    /**
     * Framework identifier.
     *
     * @var string
     */
    protected $framework = 'Bagisto';

    /**
     * 获取支付网关重定向URL
     * Get payment gateway redirect URL
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return route('security_payment.redirect');
    }

    /**
     * 检查支付方法是否可用
     * Check if payment method is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        if (! $this->cart) {
            $this->setCart();
        }

        return $this->getConfigData('active') && 
               $this->getConfigData('merchant_id') && 
               $this->getConfigData('security_code');
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

    /**
     * 获取信用卡支付URL
     * Get credit card payment URL
     *
     * @return string
     */
    public function getCreditCardPayUrl()
    {
        // 根据原Magento代码，这里应该返回支付网关的URL
        // Based on original Magento code, this should return the payment gateway URL
        return 'https://payment.security.com/gateway';
    }

    /**
     * 生成支付表单字段
     * Generate payment form fields
     *
     * @return array
     */
    public function getStandardCheckoutFormFields()
    {
        if (! $this->cart) {
            $this->setCart();
        }

        $cart = $this->getCart();
        $billingAddress = $cart->billing_address;
        $shippingAddress = $cart->shipping_address;

        // 获取商户配置信息
        // Get merchant configuration
        $merchantId = $this->getConfigData('merchant_id');
        $securityCode = $this->getConfigData('security_code');

        // 构建基础参数
        // Build basic parameters
        $params = [
            'merchant_id'     => $merchantId,
            'order_id'        => $cart->id,
            'order_amount'    => number_format($cart->grand_total, 2, '.', ''),
            'order_currency'  => $cart->cart_currency_code,
            'order_time'      => now()->format('Y-m-d H:i:s'),
            'customer_name'   => $billingAddress->first_name . ' ' . $billingAddress->last_name,
            'customer_email'  => $billingAddress->email,
            'customer_phone'  => $billingAddress->phone,
            'billing_address' => $this->formatAddress($billingAddress),
            'shipping_address'=> $this->formatAddress($shippingAddress),
            'return_url'      => route('security_payment.return'),
            'notify_url'      => route('security_payment.notify'),
            'success_url'     => route('security_payment.success'),
            'error_url'       => route('security_payment.error'),
            'version'         => $this->version,
            'framework'       => $this->framework,
            'client_ip'       => $this->getClientIp(),
        ];

        // 添加商品信息
        // Add product information
        $items = [];
        foreach ($cart->items as $item) {
            $items[] = [
                'name'     => $item->name,
                'quantity' => $item->quantity,
                'price'    => number_format($item->price, 2, '.', ''),
            ];
        }
        $params['items'] = json_encode($items);

        // 生成签名
        // Generate signature
        $params['signature'] = $this->generateSignature($params, $securityCode);

        return $params;
    }

    /**
     * 格式化地址信息
     * Format address information
     *
     * @param object $address
     * @return string
     */
    protected function formatAddress($address)
    {
        if (!$address) {
            return '';
        }

        return implode(', ', array_filter([
            $address->address1,
            $address->address2,
            $address->city,
            $address->state,
            $address->postcode,
            $address->country,
        ]));
    }

    /**
     * 生成支付签名
     * Generate payment signature
     *
     * @param array $params
     * @param string $securityCode
     * @return string
     */
    protected function generateSignature($params, $securityCode)
    {
        // 排除签名字段
        // Exclude signature field
        unset($params['signature']);
        
        // 按键名排序
        // Sort by key name
        ksort($params);
        
        // 构建签名字符串
        // Build signature string
        $signString = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $signString .= $key . '=' . $value . '&';
            }
        }
        
        // 添加密钥
        // Add security code
        $signString .= 'key=' . $securityCode;
        
        // 生成MD5签名
        // Generate MD5 signature
        return strtoupper(md5($signString));
    }

    /**
     * 验证支付签名
     * Verify payment signature
     *
     * @param array $params
     * @param string $signature
     * @return bool
     */
    public function verifySignature($params, $signature)
    {
        $securityCode = $this->getConfigData('security_code');
        $generatedSignature = $this->generateSignature($params, $securityCode);
        
        return $generatedSignature === strtoupper($signature);
    }

    /**
     * 确定信用卡类型
     * Determine credit card type
     *
     * @param string $cardNumber
     * @return string
     */
    public function determineCardType($cardNumber)
    {
        $firstDigit = substr($cardNumber, 0, 1);
        $cardLength = strlen($cardNumber);

        // 根据原Magento代码的kaleixing函数逻辑
        // Based on original Magento kaleixing function logic
        switch ($firstDigit) {
            case '4':
                return ($cardLength == 13 || $cardLength == 16) ? 'VISA' : 'UNKNOWN';
            case '5':
                return ($cardLength == 16) ? 'MASTERCARD' : 'UNKNOWN';
            case '3':
                return ($cardLength == 15) ? 'AMEX' : 'UNKNOWN';
            case '6':
                return ($cardLength == 16) ? 'DISCOVER' : 'UNKNOWN';
            default:
                return 'UNKNOWN';
        }
    }

    /**
     * 获取客户端IP地址
     * Get client IP address
     *
     * @return string
     */
    protected function getClientIp()
    {
        // 根据原Magento代码的get_client_ip函数
        // Based on original Magento get_client_ip function
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * 获取支付方法附加详情
     * Get payment method additional details
     *
     * @return array
     */
    public function getAdditionalDetails()
    {
        $details = [];
        
        if ($instructions = $this->getConfigData('instructions')) {
            $details[] = [
                'title' => trans('security_payment::app.admin.system.instructions'),
                'value' => $instructions,
            ];
        }

        return $details;
    }
}