<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;

class FlatRate extends AbstractShipping
{
    /**
     * Shipping method carrier code.
     *
     * @var string
     */
    protected $code = 'flatrate';

    /**
     * Shipping method code.
     *
     * @var string
     */
    protected $method = 'flatrate_flatrate';

    /**
     * 中文：判断固定运费是否可用（当免运费达标时隐藏）
     * English: Determine availability of Flat Rate; hide when Free Shipping qualifies.
     */
    public function isAvailable(): bool
    {
        // Respect own active setting first.
        if (! parent::isAvailable()) {
            return false;
        }

        // If Free Shipping threshold is set and reached, hide Flat Rate.
        $minimumAmount = (float) core()->getConfigData('sales.carriers.free.minimum_amount');
        if ($minimumAmount > 0) {
            $cart = Cart::getCart();
            if ($cart && ($cart->base_grand_total ?? 0) >= $minimumAmount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate rate for flatrate.
     *
     * @return \Webkul\Checkout\Models\CartShippingRate|false
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
        $cart = Cart::getCart();

        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title');
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $this->getConfigData('title');
        $cartShippingRate->method_description = $this->getConfigData('description');
        $cartShippingRate->price = 0;
        $cartShippingRate->base_price = 0;

        if ($this->getConfigData('type') == 'per_unit') {
            foreach ($cart->items as $item) {
                if ($item->getTypeInstance()->isStockable()) {
                    $cartShippingRate->price += core()->convertPrice($this->getConfigData('default_rate')) * $item->quantity;
                    $cartShippingRate->base_price += $this->getConfigData('default_rate') * $item->quantity;
                }
            }
        } else {
            $cartShippingRate->price = core()->convertPrice($this->getConfigData('default_rate'));
            $cartShippingRate->base_price = $this->getConfigData('default_rate');
        }

        return $cartShippingRate;
    }
}
