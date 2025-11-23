<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;

class OrderRecoveryController extends Controller
{
    public function recover(Request $request, OrderRepository $orderRepository, int $orderId): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            throw new InvalidSignatureException();
        }

        $order = $orderRepository->findOrFail($orderId);

        $cart = Cart::createCart([
            'customer'  => $order->customer,
            'is_active' => true,
        ]);

        Cart::setCart($cart);

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product || ! $product->status) {
                continue;
            }

            $additional = $item->additional ?? [];
            $additional['product_id'] = $item->product_id;
            $additional['quantity'] = (int) ($additional['quantity'] ?? $item->qty_ordered);

            try {
                Cart::addProduct($product, $additional);
            } catch (\Throwable $e) {
            }
        }

        $billing = $order->billing_address;
        $shipping = $order->shipping_address;

        if ($billing) {
            $billingParams = [
                'first_name' => $billing->first_name,
                'last_name'  => $billing->last_name,
                'email'      => $billing->email,
                'company_name' => $billing->company_name,
                'vat_id'       => $billing->vat_id,
                'address'    => explode(PHP_EOL, (string) $billing->address),
                'country'    => $billing->country,
                'state'      => $billing->state,
                'city'       => $billing->city,
                'postcode'   => $billing->postcode,
                'phone'      => $billing->phone,
                'use_for_shipping' => $shipping ? false : true,
            ];

            Cart::saveAddresses([
                'billing'  => $billingParams,
                'shipping' => $shipping ? [
                    'first_name' => $shipping->first_name,
                    'last_name'  => $shipping->last_name,
                    'email'      => $shipping->email,
                    'company_name' => $shipping->company_name,
                    'address'    => explode(PHP_EOL, (string) $shipping->address),
                    'country'    => $shipping->country,
                    'state'      => $shipping->state,
                    'city'       => $shipping->city,
                    'postcode'   => $shipping->postcode,
                    'phone'      => $shipping->phone,
                ] : [],
            ]);
        }

        if (! empty($order->shipping_method)) {
            Cart::saveShippingMethod($order->shipping_method);
        }

        if ($order->payment?->method) {
            Cart::savePaymentMethod(['method' => $order->payment->method]);
        }

        Cart::collectTotals();

        return redirect()->route('shop.checkout.onepage.index');
    }
}
