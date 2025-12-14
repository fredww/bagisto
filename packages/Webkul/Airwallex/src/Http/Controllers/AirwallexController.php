<?php

namespace Webkul\Airwallex\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Webkul\Airwallex\Services\AirwallexService;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class AirwallexController extends Controller
{
    protected AirwallexService $service;

    protected OrderRepository $orderRepository;

    protected InvoiceRepository $invoiceRepository;

    public function __construct(AirwallexService $service, OrderRepository $orderRepository, InvoiceRepository $invoiceRepository)
    {
        $this->service = $service;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
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
        $timestamp = (string) $request->header('x-timestamp', '');
        $signature = (string) $request->header('x-signature', '');
        $sharedSecret = (string) (core()->getConfigData('sales.payment_methods.airwallex.webhook_secret') ?: env('AIRWALLEX_WEBHOOK_SECRET', ''));
        $body = (string) $request->getContent();

        $signingValue = $timestamp;

        $tolerance = (int) (core()->getConfigData('sales.payment_methods.airwallex.webhook_tolerance_seconds') ?: env('AIRWALLEX_WEBHOOK_TOLERANCE_SECONDS', 600));
        if ($timestamp && is_numeric($timestamp) && $tolerance > 0) {
            $now = time();
            $tsVal = (int) $timestamp;
            if ($tsVal > 10000000000) {
                $tsVal = (int) floor($tsVal / 1000);
            }
            if (abs($now - $tsVal) > $tolerance) {
                Log::warning('Airwallex webhook timestamp outside tolerance', ['timestamp' => $timestamp, 'tolerance' => $tolerance]);
                return response()->json(['success' => false], 401);
            }
        }

        Log::warning('Airwallex webhook debug', ['timestamp' => $signingValue, 'signature' => $signature, 'secret_present' => (bool) $sharedSecret, 'body_length' => strlen($body)]);
        if (! $signingValue || ! $signature || ! $sharedSecret || ! $this->service->verifyWebhookSignature($signingValue, $signature, $sharedSecret, $body)) {
            Log::warning('Airwallex webhook signature invalid');

            return response()->json(['success' => false], 401);
        }

        $event = json_decode($body, true);
        if (! is_array($event)) {
            $event = $request->json()->all();
        }
        $type = (string) (Arr::get($event, 'type') ?: Arr::get($event, 'name') ?: Arr::get($event, 'event_type', ''));
        Log::info('Airwallex webhook received', ['type' => $type]);

        if ($type === 'payment_intent.succeeded') {
            $intentId = (string) (
                Arr::get($event, 'data.id')
                ?? Arr::get($event, 'data.object.id')
                ?? Arr::get($event, 'source_id', '')
            );
            Log::info('Airwallex webhook intent id extracted', ['intentId' => $intentId]);

            try {
                $intent = $intentId ? $this->service->getPaymentIntent($intentId) : ['success' => false];

                if (! Arr::get($intent, 'success')) {
                    Log::warning('Airwallex webhook intent fetch failed', ['intentId' => $intentId]);

                    return response()->json(['success' => false], 400);
                }

                $intentData = (array) Arr::get($intent, 'data', []);
                $orderNo = (string) (
                    Arr::get($intentData, 'merchant_order_id')
                    ?? Arr::get($event, 'data.merchant_order_id')
                    ?? Arr::get($event, 'data.object.merchant_order_id', '')
                );
                Log::info('Airwallex webhook order number resolved', ['merchant_order_id' => $orderNo]);

                $order = null;
                if ($orderNo) {
                    $order = $this->orderRepository->findOneByField('increment_id', $orderNo);
                    if (! $order && is_numeric($orderNo)) {
                        $order = $this->orderRepository->find((int) $orderNo);
                    }
                }

                if (! $order) {
                    Log::warning('Airwallex webhook order not found', ['merchant_order_id' => $orderNo, 'intentId' => $intentId]);

                    return response()->json(['success' => false, 'msg' => 'order_not_found'], 404);
                }

                if ($order->payment) {
                    $additional = (array) ($order->payment->additional ?? []);
                    $additional['payment_intent_id'] = $intentId;
                    $order->payment->additional = $additional;
                    $order->payment->save();
                }

                $items = [];
                foreach ($order->items as $item) {
                    $qty = (int) $item->qty_to_invoice;
                    if ($qty > 0) {
                        $items[$item->id] = $qty;
                    }
                }

                if (! empty($items)) {
                    $invoice = $this->invoiceRepository->create([
                        'order_id' => $order->id,
                        'invoice'  => [
                            'items' => $items,
                        ],
                    ]);
                    Log::info('Airwallex webhook invoice created', ['invoice_id' => $invoice->id, 'order_id' => $order->id]);
                } else {
                    Log::info('Airwallex webhook no items to invoice', ['order_id' => $order->id]);
                }

                return response()->json(['success' => true, 'order_id' => $order->id]);
            } catch (\Throwable $e) {
                Log::error('Airwallex webhook processing failed', ['error' => $e->getMessage()]);

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

        $intentId = (string) ($request->query('payment_intent_id', '') ?: $request->query('id', ''));
        if ($order && $intentId) {
            try {
                $intent = $this->service->getPaymentIntent($intentId);
                if (Arr::get($intent, 'success') && (string) Arr::get($intent, 'data.status', '') === 'succeeded') {
                    if ($order->payment) {
                        $additional = (array) ($order->payment->additional ?? []);
                        $additional['payment_intent_id'] = $intentId;
                        $order->payment->additional = $additional;
                        $order->payment->save();
                    }

                    $items = [];
                    foreach ($order->items as $item) {
                        $qty = (int) $item->qty_to_invoice;
                        if ($qty > 0) {
                            $items[$item->id] = $qty;
                        }
                    }

                    if (! empty($items)) {
                        $invoice = $this->invoiceRepository->create([
                            'order_id' => $order->id,
                            'invoice'  => [
                                'items' => $items,
                            ],
                        ]);
                        Log::info('Airwallex callback invoice created', ['invoice_id' => $invoice->id, 'order_id' => $order->id]);
                    } else {
                        Log::info('Airwallex callback no items to invoice', ['order_id' => $order->id]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Airwallex callback invoice update failed', ['error' => $e->getMessage()]);
            }
        }

        if ($order) {
            session(['order_id' => $order->id]);
        }

        return redirect()->route('shop.checkout.onepage.success');
    }
}
