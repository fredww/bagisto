<?php

namespace App\Http\Controllers;

use App\Models\FortunePayment;
use App\Services\FortunePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class FortunePayController extends Controller
{
    
    /**
     * 方法说明（中文）：重定向到支付网关，先调用 create_payment 获取跳转 URL
     * Purpose (English): Redirect to the gateway by calling create_payment to get URL
     */
    public function redirect(Request $request, FortunePayService $service)
    {
        $orderId = session('order_id');
        $orderRepo = app(OrderRepository::class);
        $order = $orderId ? $orderRepo->find($orderId) : null;

        if (! $order) {
            return \redirect()->route('shop.checkout.cart.index');
        }

        $billing = $order->billing_address;

        // Build payload from cart
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name'  => (string) $item->name,
                'price' => number_format((float) $item->price, 2, '.', ''),
                'qty'   => (int) ($item->qty_ordered ?? 1),
                'model' => (string) $item->sku,
                // Optional fields from product
                // 'link'  => '',
                // 'image' => '',
                'attr'  => [],
            ];
        }

        $address = trim((string) $billing->address ?? '');

        $orderNo = (string) ($order->increment_id ?? $order->id);

        $data = [
            'order_no'   => $orderNo,
            'invoice_id' => 'INV-' . $orderNo . '-' . time(),
            'currency'   => (string) $order->order_currency_code,
            'amount'     => number_format((float) $order->grand_total, 2, '.', ''),
            'first_name' => (string) ($billing->first_name ?? ''),
            'last_name'  => (string) ($billing->last_name ?? ''),
            'email'      => (string) ($billing->email ?? ''),
            'telephone'  => (string) ($billing->phone ?? ''),
            'address'    => $address,
            'city'       => (string) ($billing->city ?? ''),
            'country'    => (string) ($billing->country ?? ''),
            'zip_code'   => (string) ($billing->postcode ?? ''),
            'zone'       => (string) ($billing->state ?? ''),
            'subject'    => 'Order #' . $orderNo,
            'body'       => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        $resp = $service->createPayment($data);

        $result = Arr::get($resp, 'result', []);

        if (Arr::get($result, 'success') && Arr::get($result, 'redirect') && Arr::get($result, 'url')) {
            return \redirect()->away((string) Arr::get($result, 'url'));
        }

        return \view('fortune.error', [
            'message' => (string) Arr::get($result, 'msg', 'Unable to start payment'),
        ]);
    }
    /**
     * 发起支付下单
     * Purpose: Handle POST to create a payment and return gateway response structure
     */
    public function createPayment(Request $request, FortunePayService $service)
    {
        // Basic validation (strict fields per spec)
        $data = $request->all();

        $required = ['order_no', 'invoice_id', 'currency', 'amount'];
        foreach ($required as $field) {
            if (!$request->has($field)) {
                return response()->json([
                    'code' => 422,
                    'result' => [
                        'success' => false,
                        'msg' => "Missing required field: {$field}",
                    ],
                    'msg' => 'Validation error',
                ], 422);
            }
        }

        $resp = $service->createPayment($data);
        return response()->json($resp, Arr::get($resp, 'code', 200));
    }

    /**
     * 异步通知回调
     * Purpose: Handle server-side POST notify from gateway; verify signature and update status
     */
    public function notify(Request $request, FortunePayService $service)
    {
        $payload = $request->all();
        $config = $service->getConfig();
        if (!empty($config['debug_log'])) {
            Log::channel('fortune_notify')->info('FortunePay notify payload', ['payload' => $payload]);
        }
        $valid = $service->verifyToken($payload);

        if (!$valid) {
            Log::warning('FortunePay notify invalid signature', ['payload' => $payload]);
            return response()->json(['code' => 400, 'msg' => 'Invalid signature'], 400);
        }

        $failureCode = (string) ($payload['failure_code'] ?? 'failed');
        $orderNo = (string) ($payload['order_no'] ?? '');
        $invoiceId = (string) ($payload['invoice_id'] ?? '');

        $status = $failureCode === 'success' ? 'success' : 'failed';
        $service->updatePaymentStatus($orderNo, $invoiceId, $status, [
            'failure_code' => $failureCode,
            'failure_msg' => (string) ($payload['failure_msg'] ?? ''),
            'notified_at' => now(),
        ]);

        if ($status === 'success') {
            $service->createInvoiceIfNeeded($orderNo);
        }

        return response('ok',200);
    }

    /**
     * 同步返回处理
     * Purpose: Handle GET return; verify signature, update status, and render result view
     */
    public function return(Request $request, FortunePayService $service)
    {
        $params = $request->all();
        $config = $service->getConfig();
        if (!empty($config['debug_log'])) {
            Log::channel('fortune_return')->info('FortunePay return params', ['params' => $params]);
        }
        $valid = $service->verifyToken($params);

        $failureCode = (string) ($params['failure_code'] ?? 'failed');
        $orderNo = (string) ($params['order_no'] ?? '');
        $invoiceId = (string) ($params['invoice_id'] ?? '');
        $status = $failureCode === 'success' ? 'success' : 'failed';

        // 用户取消支付
        $failure_msg = isset($params['failure_msg']) ? (string) $params['failure_msg'] : '';
        if ($failure_msg === '用户取消') {
            $failure_msg = 'canceled';
        }
        $failure_msg = strtolower($failure_msg);

        if ($valid) {
            $service->updatePaymentStatus($orderNo, $invoiceId, $status, [
                'failure_code' => $failureCode,
                'failure_msg' => $failure_msg,
                'returned_at' => now(),
            ]);
        } else {
            Log::warning('FortunePay return invalid signature', ['params' => $params]);
        }

        // 只有成功且无失败消息时才创建发票
        if ($valid && $status === 'success' && ($failure_msg === '' || $failure_msg === 'success')) {
            $service->createInvoiceIfNeeded($orderNo);
            $orderRepo = app(\Webkul\Sales\Repositories\OrderRepository::class);
            $order = $orderRepo->findOneByField('increment_id', $orderNo);
            if ($order) {
                session(['order_id' => $order->id]);
            }
            return \redirect()->route('shop.checkout.onepage.success');
        }

        $payment = FortunePayment::where('order_no', $orderNo)->where('invoice_id', $invoiceId)->latest('id')->first();

        return \view('fortune.return', [
            'valid' => $valid,
            'failureCode' => $failureCode,
            'failureMsg' => $failure_msg,
            'payment' => $payment,
        ]);
    }

    /**
     * 方法说明（中文）：支付成功回调（与 return 相同处理流程）
     * Purpose (English): Handle success callback; same business logic as return
     */
    public function success(Request $request, FortunePayService $service)
    {
        $params = $request->all();
        $config = $service->getConfig();
        if (!empty($config['debug_log'])) {
            Log::channel('fortune_return')->info('FortunePay success params', ['params' => $params]);
        }
        $valid = $service->verifyToken($params);

        $failureCode = (string) ($params['failure_code'] ?? 'failed');
        $orderNo = (string) ($params['order_no'] ?? '');
        $invoiceId = (string) ($params['invoice_id'] ?? '');
        $status = $failureCode === 'success' ? 'success' : 'failed';

        $failure_msg = isset($params['failure_msg']) ? (string) $params['failure_msg'] : '';
        if ($failure_msg === '用户取消') {
            $failure_msg = 'canceled';
        }
        $failure_msg = strtolower($failure_msg);

        if ($valid) {
            $service->updatePaymentStatus($orderNo, $invoiceId, $status, [
                'failure_code' => $failureCode,
                'failure_msg' => $failure_msg,
                'returned_at' => now(),
            ]);
        } else {
            $params['token'] = $params['token'] ?? '';
            $params['valid'] = $valid;
            Log::warning('FortunePay success invalid signature', ['params' => $params]);
        }

        // 只有成功且无失败消息时才创建发票
        if ($valid && $status === 'success' && ($failure_msg === '' || $failure_msg === 'success')) {
            $service->createInvoiceIfNeeded($orderNo);
            $orderRepo = app(\Webkul\Sales\Repositories\OrderRepository::class);
            $order = $orderRepo->findOneByField('increment_id', $orderNo);
            if ($order) {
                session(['order_id' => $order->id]);
            }
            return \redirect()->route('shop.checkout.onepage.success');
        }

        $payment = FortunePayment::where('order_no', $orderNo)->where('invoice_id', $invoiceId)->latest('id')->first();

        return \view('fortune.return', [
            'valid' => $valid,
            'failureCode' => $failureCode,
            'failureMsg' => $failure_msg,
            'payment' => $payment,
        ]);
    }

    /**
     * 查询支付状态
     * Purpose: Query payment status locally by order_no or invoice_id
     */
    public function query(Request $request, FortunePayService $service)
    {
        $orderNo = $request->query('order_no');
        $invoiceId = $request->query('invoice_id');
        $payment = $service->queryLocal($orderNo, $invoiceId);

        if (!$payment) {
            return response()->json(['code' => 404, 'msg' => 'Payment not found'], 404);
        }

        return response()->json([
            'code' => 200,
            'result' => [
                'success' => $payment->status === 'success',
                'status' => $payment->status,
                'order_no' => $payment->order_no,
                'invoice_id' => $payment->invoice_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'pay_no' => $payment->pay_no,
                'redirect' => (bool) $payment->redirect,
                'url' => $payment->url,
            ],
            'msg' => null,
        ]);
    }
}