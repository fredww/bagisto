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
            try {
                Cart::addProduct($item->product, $item->additional ?? ['quantity' => (int) $item->qty_ordered]);
            } catch (\Exception $e) {
            }
        }

        return redirect()->route('shop.checkout.onepage.index');
    }
}

