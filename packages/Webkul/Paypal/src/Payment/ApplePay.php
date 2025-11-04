<?php

namespace Webkul\Paypal\Payment;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;

/**
 * Apple Pay支付方法类 - 专门用于Apple Pay集成
 * Apple Pay Payment Method Class - Dedicated for Apple Pay integration
 */
class ApplePay extends Paypal
{
    /**
     * Client ID.
     *
     * @var string
     */
    protected $clientId;

    /**
     * Client secret.
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'paypal_apple_pay';

    /**
     * Apple Pay Merchant ID.
     *
     * @var string
     */
    protected $merchantId;

    /**
     * Paypal partner attribution id.
     *
     * @var string
     */
    protected $paypalPartnerAttributionId = 'Bagisto_ApplePay';

    /**
     * 构造函数
     * Constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * 检查Apple Pay是否可用
     * Check if Apple Pay is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        // 首先检查基本配置是否启用
        // First check if basic configuration is enabled
        if (! parent::isAvailable()) {
            return false;
        }

        // 检查是否配置了必要的Apple Pay设置
        // Check if necessary Apple Pay settings are configured
        if (empty($this->clientId) || empty($this->clientSecret)) {
            return false;
        }

        return true;
    }

    /**
     * Returns PayPal HTTP client instance with environment that has access
     * credentials context. Use this instance to invoke PayPal APIs, provided the
     * credentials have access.
     *
     * @return PayPalCheckoutSdk\Core\PayPalHttpClient
     */
    public function client()
    {
        return new PayPalHttpClient($this->environment());
    }

    /**
     * 创建Apple Pay订单
     * Create Apple Pay order for approval of client.
     *
     * @param  array  $body
     * @return HttpResponse
     */
    public function createOrder($body)
    {
        $request = new OrdersCreateRequest;
        $request->headers['PayPal-Partner-Attribution-Id'] = $this->paypalPartnerAttributionId;
        $request->prefer('return=representation');
        
        // Remove payment_source if present - PayPal JavaScript SDK handles this automatically
        // When using PayPal's JavaScript SDK with Apple Pay, payment_source should NOT be
        // included in the initial order creation. PayPal SDK handles payment source selection
        // when the user approves the payment.
        unset($body['payment_source']);
        
        $request->body = $body;

        return $this->client()->execute($request);
    }

    /**
     * 捕获Apple Pay订单
     * Capture Apple Pay order after approval.
     *
     * @param  string  $orderId
     * @return HttpResponse
     */
    public function captureOrder($orderId)
    {
        $request = new OrdersCaptureRequest($orderId);
        $request->headers['PayPal-Partner-Attribution-Id'] = $this->paypalPartnerAttributionId;
        $request->prefer('return=representation');

        return $this->client()->execute($request);
    }

    /**
     * 获取订单详情
     * Get order details.
     *
     * @param  string  $orderId
     * @return HttpResponse
     */
    public function getOrder($orderId)
    {
        return $this->client()->execute(new OrdersGetRequest($orderId));
    }

    /**
     * 获取捕获ID
     * Get capture id.
     *
     * @param  string  $orderId
     * @return string
     */
    public function getCaptureId($orderId)
    {
        $paypalOrderDetails = $this->getOrder($orderId);

        return $paypalOrderDetails->result->purchase_units[0]->payments->captures[0]->id;
    }

    /**
     * 退款订单
     * Refund order.
     *
     * @return HttpResponse
     */
    public function refundOrder($captureId, $body = [])
    {
        $request = new CapturesRefundRequest($captureId);
        $request->headers['PayPal-Partner-Attribution-Id'] = $this->paypalPartnerAttributionId;
        $request->body = $body;

        return $this->client()->execute($request);
    }

    /**
     * 获取Apple Pay商户ID
     * Get Apple Pay Merchant ID.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * 获取支持的货币
     * Get supported currencies for Apple Pay.
     *
     * @return array
     */
    public function getSupportedCurrencies()
    {
        $acceptedCurrency = $this->getConfigData('accepted_currencies') ?: 'USD,EUR,GBP';
        
        return array_map('trim', explode(',', $acceptedCurrency));
    }

    /**
     * 检查当前货币是否支持
     * Check if current currency is supported.
     *
     * @return bool
     */
    public function isCurrencySupported()
    {
        $currentCurrency = core()->getCurrentCurrencyCode();
        $supportedCurrencies = $this->getSupportedCurrencies();
        
        return in_array($currentCurrency, $supportedCurrencies);
    }

    /**
     * Return paypal redirect url.
     *
     * @return string
     */
    public function getRedirectUrl() 
    {
        // Apple Pay doesn't need redirect URL as it's handled via JavaScript
        return '';
    }

    /**
     * 获取客户ID
     * Get customer ID for Apple Pay.
     *
     * @return string|null
     */
    protected function getCustomerId()
    {
        $customer = auth()->guard('customer')->user();
        
        return $customer ? $customer->id : null;
    }

    /**
     * Set up and return PayPal PHP SDK environment with PayPal access credentials.
     * This sample uses SandboxEnvironment. In production, use LiveEnvironment.
     *
     * @return PayPalCheckoutSdk\Core\SandboxEnvironment|PayPalCheckoutSdk\Core\ProductionEnvironment
     */
    protected function environment()
    {
        $isSandbox = $this->getConfigData('sandbox') ?: false;

        if ($isSandbox) {
            return new SandboxEnvironment($this->clientId, $this->clientSecret);
        }

        return new ProductionEnvironment($this->clientId, $this->clientSecret);
    }

    /**
     * 初始化属性
     * Initialize properties.
     *
     * @return void
     */
    protected function initialize()
    {
        $this->clientId = $this->getConfigData('client_id') ?: '';
        $this->clientSecret = $this->getConfigData('client_secret') ?: '';
        $this->merchantId = $this->getConfigData('merchant_id') ?: '';
    }
}