<?php

namespace Webkul\Airwallex\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Webkul\Airwallex\Services\AirwallexService;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;

class AirwallexController extends Controller
{
    protected AirwallexService $service;

    protected OrderRepository $orderRepository;

    public function __construct(AirwallexService $service, OrderRepository $orderRepository)
    {
        $this->service = $service;
        $this->orderRepository = $orderRepository;
    }

    public function redirect(Request $request)
    {
        $orderId = session('order_id');
        $order = $orderId ? $this->orderRepository->find($orderId) : null;

        if (! $order) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $amount = (string) number_format((float) $order->grand_total, 2, '.', '');
        $currency = (string) $order->order_currency_code;
        $orderNo = (string) ($order->increment_id ?? $order->id);

        $successUrl = route('airwallex.callback', ['order_no' => $orderNo]);
        $cancelUrl = route('shop.checkout.cart.index');

        $payload = [
            'amount'            => $amount,
            'currency'          => $currency,
            'merchant_order_id' => $orderNo,
            'title'             => 'Order '.$orderNo,
            'reusable'          => false,
            'return_url'        => $successUrl,
            'cancel_url'        => $cancelUrl,
        ];

        $resp = $this->service->createPaymentLink($payload);
        if (! Arr::get($resp, 'success')) {
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Unable to initialize Airwallex payment');
        }

        $url = (string) Arr::get($resp, 'data.url');
        if (! $url) {
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Invalid Airwallex response');
        }

        return redirect()->away($url);
    }

    public function webhook(Request $request)
    {
        $nonce = (string) $request->header('x-nonce', '');
        $signature = (string) $request->header('x-signature', '');
        $sharedSecret = (string) (core()->getConfigData('sales.payment_methods.airwallex.webhook_secret') ?? '');

        if (! $nonce || ! $signature || ! $sharedSecret || ! $this->service->verifyWebhookSignature($nonce, $signature, $sharedSecret)) {
            Log::warning('Airwallex webhook signature invalid');

            return response()->json(['success' => false], 401);
        }

        $event = $request->json()->all();
        $type = (string) Arr::get($event, 'type', '');

        if ($type === 'payment_intent.succeeded') {
            $intentId = (string) Arr::get($event, 'data.id');
            try {
                $cart = Cart::getCart();
                $order = $this->orderRepository->create([
                    'payment' => [
                        'method'       => 'airwallex',
                        'method_title' => 'Airwallex',
                        'additional'   => [
                            'payment_intent_id' => $intentId,
                        ],
                    ],
                ]);

                return response()->json(['success' => true, 'order_id' => $order->id]);
            } catch (\Throwable $e) {
                Log::error('Airwallex webhook order creation failed', ['error' => $e->getMessage()]);

                return response()->json(['success' => false], 500);
            }
        }

        return response()->json(['success' => true]);
    }

    public function status(string $intentId)
    {
        $resp = $this->service->getPaymentIntent($intentId);
        if (! Arr::get($resp, 'success')) {
            return response()->json(['success' => false], 400);
        }

        return response()->json($resp['data']);
    }

    public function refund(Request $request)
    {
        $payload = $request->validate([
            'payment_intent_id' => 'required|string',
            'amount'            => 'required|numeric',
            'currency'          => 'required|string',
        ]);

        $resp = $this->service->createRefund([
            'payment_intent_id' => (string) $payload['payment_intent_id'],
            'amount'            => (string) number_format((float) $payload['amount'], 2, '.', ''),
            'currency'          => (string) $payload['currency'],
        ]);

        if (! Arr::get($resp, 'success')) {
            return response()->json(['success' => false], 400);
        }

        return response()->json($resp['data']);
    }

    public function callback(Request $request)
    {
        $orderNo = (string) $request->query('order_no', '');
        $order = $orderNo ? $this->orderRepository->findOneByField('increment_id', $orderNo) : null;

        if (! $order) {
            $orderId = (int) $request->query('order_id', 0);
            $order = $orderId ? $this->orderRepository->find($orderId) : null;
        }

        if ($order) {
            session(['order_id' => $order->id]);
        }

        return redirect()->route('shop.checkout.onepage.success');
    }
}
