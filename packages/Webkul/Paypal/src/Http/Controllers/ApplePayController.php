<?php

namespace Webkul\Paypal\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Checkout\Facades\Cart;
use Webkul\Paypal\Payment\ApplePay;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

/**
 * Apple Pay控制器 - 处理Apple Pay支付流程
 * Apple Pay Controller - Handles Apple Pay payment flow
 */
class ApplePayController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ApplePay $applePay,
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    /**
     * 创建Apple Pay订单
     * Create Apple Pay order.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        try {
            // 检查Apple Pay是否可用
            // Check if Apple Pay is available
            if (! $this->applePay->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => trans('paypal::app.errors.apple-pay-disabled'),
                ], 400);
            }

            // 检查货币是否支持
            // Check if currency is supported
            if (! $this->applePay->isCurrencySupported()) {
                return response()->json([
                    'success' => false,
                    'message' => trans('paypal::app.errors.currency-not-supported'),
                ], 400);
            }

            $cart = Cart::getCart();

            if (! $cart) {
                return response()->json([
                    'success' => false,
                    'message' => trans('shop::app.checkout.cart.index.cart-empty'),
                ], 400);
            }

            // 构建PayPal订单数据
            // Build PayPal order data
            $orderData = $this->buildOrderData($cart);

            // 创建PayPal订单
            // Create PayPal order
            $response = $this->applePay->createOrder($orderData);

            if ($response->statusCode !== 201) {
                return response()->json([
                    'success' => false,
                    'message' => trans('paypal::app.errors.something-went-wrong'),
                ], 500);
            }

            return response()->json([
                'success' => true,
                'order_id' => $response->result->id,
                'client_id' => $this->applePay->getConfigData('client_id'),
                'merchant_id' => $this->applePay->getMerchantId(),
                'currency' => core()->getCurrentCurrencyCode(),
                'amount' => $cart->grand_total,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('paypal::app.errors.something-went-wrong'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 捕获Apple Pay支付
     * Capture Apple Pay payment.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * 捕获Apple Pay支付订单
     * Capture Apple Pay order.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function captureOrder(Request $request)
    {
        try {
            $orderId = $request->input('order_id');

            if (! $orderId) {
                return response()->json([
                    'success' => false,
                    'message' => trans('paypal::app.errors.invalid-order-id'),
                ], 400);
            }

            // 捕获PayPal订单
            // Capture PayPal order
            $response = $this->applePay->captureOrder($orderId);

            if ($response->statusCode !== 201) {
                return response()->json([
                    'success' => false,
                    'message' => trans('paypal::app.errors.capture-failed'),
                ], 500);
            }

            // 创建Bagisto订单
            // Create Bagisto order
            $order = $this->createBagistoOrder($response->result);

            return response()->json([
                'success' => true,
                'order' => new OrderResource($order),
                'redirect_url' => route('shop.checkout.onepage.success'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('paypal::app.errors.something-went-wrong'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 检查Apple Pay可用性
     * Check Apple Pay availability.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability()
    {
        try {
            $isAvailable = $this->applePay->isAvailable() && $this->applePay->isCurrencySupported();

            return response()->json([
                'success' => true,
                'available' => $isAvailable,
                'supported_currencies' => $this->applePay->getSupportedCurrencies(),
                'current_currency' => core()->getCurrentCurrencyCode(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 构建PayPal订单数据
     * Build PayPal order data.
     *
     * @param  mixed  $cart
     * @return array
     */
    protected function buildOrderData($cart)
    {
        $currencyCode = core()->getCurrentCurrencyCode();
        
        return [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'default',
                    'amount' => [
                        'currency_code' => $currencyCode,
                        'value' => number_format($cart->grand_total, 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $currencyCode,
                                'value' => number_format($cart->sub_total, 2, '.', ''),
                            ],
                            'tax_total' => [
                                'currency_code' => $currencyCode,
                                'value' => number_format($cart->tax_total, 2, '.', ''),
                            ],
                            'shipping' => [
                                'currency_code' => $currencyCode,
                                'value' => number_format($cart->shipping_amount, 2, '.', ''),
                            ],
                            'discount' => [
                                'currency_code' => $currencyCode,
                                'value' => number_format(abs($cart->discount_amount), 2, '.', ''),
                            ],
                        ],
                    ],
                    'items' => $this->buildOrderItems($cart),
                    'shipping' => $this->buildShippingInfo($cart),
                ],
            ],
            'payment_source' => [
                'applepay' => [
                    'attributes' => [
                        'customer' => [
                            'id' => auth()->guard('customer')->id(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 构建订单商品数据
     * Build order items data.
     *
     * @param  mixed  $cart
     * @return array
     */
    protected function buildOrderItems($cart)
    {
        $items = [];
        $currencyCode = core()->getCurrentCurrencyCode();

        foreach ($cart->items as $item) {
            $items[] = [
                'name' => $item->name,
                'unit_amount' => [
                    'currency_code' => $currencyCode,
                    'value' => number_format($item->price, 2, '.', ''),
                ],
                'quantity' => $item->quantity,
                'sku' => $item->sku,
            ];
        }

        return $items;
    }

    /**
     * 构建配送信息
     * Build shipping information.
     *
     * @param  mixed  $cart
     * @return array
     */
    protected function buildShippingInfo($cart)
    {
        $shippingAddress = $cart->shipping_address;

        if (! $shippingAddress) {
            return [];
        }

        return [
            'name' => [
                'full_name' => $shippingAddress->first_name . ' ' . $shippingAddress->last_name,
            ],
            'address' => [
                'address_line_1' => $shippingAddress->address,
                'admin_area_2' => $shippingAddress->city,
                'admin_area_1' => $shippingAddress->state,
                'postal_code' => $shippingAddress->postcode,
                'country_code' => $shippingAddress->country,
            ],
        ];
    }

    /**
     * 创建Bagisto订单
     * Create Bagisto order.
     *
     * @param  mixed  $paypalOrder
     * @return mixed
     */
    protected function createBagistoOrder($paypalOrder)
    {
        $cart = Cart::getCart();

        // 准备订单数据
        // Prepare order data
        $orderData = [
            'payment' => [
                'method' => 'paypal_apple_pay',
                'method_title' => 'Apple Pay via PayPal',
                'additional' => [
                    'paypal_order_id' => $paypalOrder->id,
                    'capture_id' => $paypalOrder->purchase_units[0]->payments->captures[0]->id ?? null,
                ],
            ],
        ];

        // 创建订单
        // Create order
        $order = $this->orderRepository->create($orderData);

        // 创建发票
        // Create invoice
        if ($order && isset($paypalOrder->purchase_units[0]->payments->captures[0])) {
            $this->invoiceRepository->create([
                'order_id' => $order->id,
                'state' => 'paid',
                'transaction_id' => $paypalOrder->purchase_units[0]->payments->captures[0]->id,
            ]);
        }

        return $order;
    }
}