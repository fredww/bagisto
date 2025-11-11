<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;

class Free extends AbstractShipping
{
    /**
     * Shipping method carrier code.
     *
     * @var string
     */
    protected $code = 'free';

    /**
     * Shipping method code.
     *
     * @var string
     */
    protected $method = 'free_free';

    /**
     * 中文：判断免运费是否可用（订单金额达到配置门槛时启用）
     * English: Determine availability of Free Shipping based on configured threshold.
     */
    public function isAvailable(): bool
    {
        // Honor carrier active setting first.
        if (! parent::isAvailable()) {
            return false;
        }

        // Fetch threshold (base currency). If not set or <= 0, treat as not qualifying.
        $minimumAmount = (float) $this->getConfigData('minimum_amount');
        if ($minimumAmount <= 0) {
            return false;
        }

        // Get current cart and compare base grand total to threshold.
        $cart = Cart::getCart();
        if (! $cart) {
            return false;
        }

        // Compare totals; uses base_grand_total to avoid currency conversion issues.
        return ($cart->base_grand_total ?? 0) >= $minimumAmount;
    }

    /**
     * Calculate rate for free shipping.
     *
     * @return CartShippingRate|false
     */
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $this->getRate();
    }

    /**
     * Get rate.
     */
    public function getRate(): CartShippingRate
    {
        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title');
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $this->getConfigData('title');
        $cartShippingRate->method_description = $this->getConfigData('description');
        $cartShippingRate->price = 0;
        $cartShippingRate->base_price = 0;

        return $cartShippingRate;
    }
}
