<?php

namespace App\Http\Controllers;

use App\Services\AsiabillService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * 方法说明（中文）：Asiabill 站内支付控制器，负责页面渲染、会话、扣款与回调
 * Purpose (English): Asiabill on-site payment controller for page render, session, charge and callbacks
 */
class AsiabillController extends Controller
{
    /**
     * 站内支付页面
     * Render on-site payment page with JS SDK
     */
    public function onsite(Request $request, AsiabillService $service)
    {
        $orderId = session('order_id');
        $orderRepo = app(\Webkul\Sales\Repositories\OrderRepository::class);
        $order = $orderId ? $orderRepo->find($orderId) : null;

        if (! $order) {
            $cart = \Webkul\Checkout\Facades\Cart::getCart();
            if ($cart) {
                $data = (new \Webkul\Sales\Transformers\OrderResource($cart))->jsonSerialize();
                $order = $orderRepo->create($data);
                \Webkul\Checkout\Facades\Cart::deActivateCart();
                session()->flash('order_id', $order->id);
            } else {
                return \redirect()->route('shop.checkout.cart.index');
            }
        }

        $scriptTag = $service->getJsScript();

        return \view('payment.asiabill.onsite', [
            'scriptTag' => $scriptTag,
            'order'     => $order,
        ]);
    }

    /**
     * 获取 sessionToken 与客户ID
     * Get sessionToken and customerId
     */
    public function sessionToken(Request $request, AsiabillService $service)
    {
        $orderId = session('order_id');
        $orderRepo = app(\Webkul\Sales\Repositories\OrderRepository::class);
        $order = $orderId ? $orderRepo->find($orderId) : null;
        $billing = $order ? $order->billing_address : null;
        $tokenResp = $service->getSessionToken();
        $customerId = $service->ensureCustomerId([
            'email'      => $billing->email ?? '',
            'first_name' => $billing->first_name ?? '',
            'last_name'  => $billing->last_name ?? '',
            'phone'      => $billing->phone ?? '',
            'address'    => $billing->address ?? '',
            'city'       => $billing->city ?? '',
            'country'    => $billing->country ?? '',
            'state'      => $billing->state ?? '',
            'postcode'   => $billing->postcode ?? '',
        ]);

        return response()->json([
            'code' => Arr::get($tokenResp, 'code', '0000'),
            'data' => [
                'sessionToken' => Arr::get($tokenResp, 'data.sessionToken'),
                'customerId'   => $customerId,
            ],
        ]);
    }

    /**
     * 发起扣款
     * Confirm charge after front-end payment method creation
     */
    public function confirmCharge(Request $request, AsiabillService $service)
    {
        $orderId = session('order_id');
        $orderRepo = app(\Webkul\Sales\Repositories\OrderRepository::class);
        $order = $orderId ? $orderRepo->find($orderId) : null;
        $billing = $order ? $order->billing_address : null;

        $validated = $request->validate([
            'customerPaymentMethodId' => 'required|string',
            'customerId'              => 'required|string',
        ]);

        $goodsDetails = [];
        if ($order) {
            foreach ($order->items as $item) {
                $goodsDetails[] = [
                    'goodsCount' => (string) ($item->qty_ordered ?? 1),
                    'goodsPrice' => number_format((float) $item->price, 2, '.', ''),
                    'goodsTitle' => (string) $item->name,
                ];
            }
        }

        $cfg = $service->getConfig();

        $data = [
            'body' => [
                'callbackUrl'            => $cfg['callbackUrl'],
                'customerId'             => (string) $validated['customerId'],
                'customerPaymentMethodId'=> (string) $validated['customerPaymentMethodId'],
                'shipping'               => [
                    'address' => [
                        'line1'      => (string) ($billing->address ?? ''),
                        'line2'      => '',
                        'city'       => (string) ($billing->city ?? ''),
                        'country'    => (string) ($billing->country ?? ''),
                        'state'      => (string) ($billing->state ?? ''),
                        'postalCode' => (string) ($billing->postcode ?? ''),
                    ],
                    'email'     => (string) ($billing->email ?? ''),
                    'firstName' => (string) ($billing->first_name ?? ''),
                    'lastName'  => (string) ($billing->last_name ?? ''),
                    'phone'     => (string) ($billing->phone ?? ''),
                ],
                'goodsDetails' => $goodsDetails,
                'isMobile'     => request()->header('X-Device', '') === 'mobile' ? 1 : 0,
                'customerIp'   => request()->ip(),
                'orderAmount'  => number_format((float) ($order->grand_total ?? 0), 2, '.', ''),
                'orderCurrency'=> (string) ($order->order_currency_code ?? 'USD'),
                'orderNo'      => (string) ($order ? ($order->increment_id ?? $order->id) : ('TEMP-'.time())),
                'platform'     => 'php_SDK',
                'remark'       => '',
                'returnUrl'    => $cfg['returnUrl'],
                'webSite'      => (string) request()->getHost(),
                'tokenType'    => '',
            ],
        ];

        try {
            $resp = $service->confirmCharge($data);
        } catch (\Throwable $e) {
            Log::error('Asiabill confirmCharge exception', ['e' => $e->getMessage()]);

            return response()->json(['code' => 500, 'msg' => 'Charge exception'], 500);
        }

        $code = Arr::get($resp, 'code');
        if ($code === '0000' || $code === '00000') {
            if ($order) {
                $orderRepo->updateOrderStatus($order, \Webkul\Sales\Models\Order::STATUS_PROCESSING);
            }

            return response()->json(['code' => $code, 'success' => true, 'data' => Arr::get($resp, 'data', [])]);
        }

        return response()->json(['code' => $code, 'success' => false, 'data' => Arr::get($resp, 'data', [])]);
    }

    /**
     * 异步通知回调（根据返回信息更新订单）
     * Notify callback to update order state
     */
    public function notify(Request $request)
    {
        $payload = $request->all();
        Log::info('Asiabill notify payload', ['payload' => $payload]);
        $orderNo = (string) Arr::get($payload, 'orderNo');
        $status = (string) Arr::get($payload, 'status');

        $orderRepo = app(\Webkul\Sales\Repositories\OrderRepository::class);
        $order = $orderRepo->findOneByField('increment_id', $orderNo);
        if ($order) {
            if ($status === 'success') {
                $orderRepo->updateOrderStatus($order, \Webkul\Sales\Models\Order::STATUS_PROCESSING);
            }
        }

        return response('ok', 200);
    }

    /**
     * 同步返回
     * Return callback
     */
    public function return(Request $request)
    {
        $status = (string) $request->get('status', 'failed');
        if ($status === 'success') {
            return \redirect()->route('shop.checkout.onepage.success');
        }

        return \redirect()->route('shop.checkout.cart.index');
    }

    /**
     * 查询本地支付状态（基于订单）
     * Query local payment status
     */
    public function query(Request $request)
    {
        $orderNo = (string) $request->query('order_no');
        $orderRepo = app(\Webkul\Sales\Repositories\OrderRepository::class);
        $order = $orderRepo->findOneByField('increment_id', $orderNo);
        if (! $order) {
            return response()->json(['code' => 404, 'msg' => 'Order not found'], 404);
        }

        return response()->json([
            'code'   => 200,
            'result' => [
                'success' => in_array($order->status, [\Webkul\Sales\Models\Order::STATUS_PROCESSING, \Webkul\Sales\Models\Order::STATUS_COMPLETED]),
                'status'  => $order->status,
                'order_no'=> $order->increment_id,
            ],
        ]);
    }
}
