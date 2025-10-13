<?php

namespace Webkul\SecurityPayment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\SecurityPayment\Payment\SecurityPayment;

/**
 * 安全支付控制器 - 处理支付流程
 * Security Payment Controller - Handle payment process
 */
class SecurityPaymentController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Order repository instance.
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * SecurityPayment instance.
     *
     * @var SecurityPayment
     */
    protected $securityPayment;

    /**
     * Create a new controller instance.
     *
     * @param OrderRepository $orderRepository
     * @param SecurityPayment $securityPayment
     */
    public function __construct(
        OrderRepository $orderRepository,
        SecurityPayment $securityPayment
    ) {
        $this->orderRepository = $orderRepository;
        $this->securityPayment = $securityPayment;
    }

    /**
     * 重定向到支付网关
     * Redirect to payment gateway
     *
     * @return \Illuminate\View\View
     */
    public function redirect()
    {
        if (! Cart::getCart()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();
        
        // 生成支付表单字段
        // Generate payment form fields
        $this->securityPayment->setCart($cart);
        $formFields = $this->securityPayment->getStandardCheckoutFormFields();
        $paymentUrl = $this->securityPayment->getCreditCardPayUrl();

        return view('security_payment::redirect', compact('formFields', 'paymentUrl'));
    }

    /**
     * 处理支付网关通知
     * Handle payment gateway notification
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function notify(Request $request)
    {
        $params = $request->all();
        
        // 验证签名
        // Verify signature
        if (! isset($params['signature'])) {
            return response('Missing signature', 400);
        }

        $signature = $params['signature'];
        unset($params['signature']);

        if (! $this->securityPayment->verifySignature($params, $signature)) {
            return response('Invalid signature', 400);
        }

        // 处理支付结果
        // Process payment result
        $orderId = $params['order_id'] ?? null;
        $paymentStatus = $params['payment_status'] ?? 'failed';
        $transactionId = $params['transaction_id'] ?? null;

        if ($orderId && $paymentStatus === 'success') {
            try {
                $order = $this->orderRepository->findOrFail($orderId);
                
                // 更新订单状态
                // Update order status
                $this->orderRepository->update([
                    'status' => 'processing',
                ], $orderId);

                // 创建支付记录
                // Create payment record
                if ($transactionId) {
                    $order->payment()->update([
                        'additional' => json_encode([
                            'transaction_id' => $transactionId,
                            'payment_status' => $paymentStatus,
                            'gateway_response' => $params,
                        ]),
                    ]);
                }

                return response('OK', 200);
            } catch (\Exception $e) {
                Log::error('SecurityPayment notify error: ' . $e->getMessage());
                return response('Error processing payment', 500);
            }
        }

        return response('Payment failed', 400);
    }

    /**
     * 处理支付返回
     * Handle payment return
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function return(Request $request)
    {
        $params = $request->all();
        
        // 验证签名
        // Verify signature
        if (! isset($params['signature'])) {
            return redirect()->route('security_payment.error')
                ->with('error', trans('security_payment::app.shop.payment.invalid_signature'));
        }

        $signature = $params['signature'];
        unset($params['signature']);

        if (! $this->securityPayment->verifySignature($params, $signature)) {
            return redirect()->route('security_payment.error')
                ->with('error', trans('security_payment::app.shop.payment.invalid_signature'));
        }

        // 根据支付状态重定向
        // Redirect based on payment status
        $paymentStatus = $params['payment_status'] ?? 'failed';
        
        if ($paymentStatus === 'success') {
            return redirect()->route('security_payment.success', $params);
        } else {
            return redirect()->route('security_payment.failure', $params);
        }
    }

    /**
     * 支付成功页面
     * Payment success page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function success(Request $request)
    {
        $orderId = $request->get('order_id');
        $transactionId = $request->get('transaction_id');
        
        $order = null;
        if ($orderId) {
            try {
                $order = $this->orderRepository->findOrFail($orderId);
            } catch (\Exception $e) {
                // Order not found
            }
        }

        return view('security_payment::success', compact('order', 'transactionId'));
    }

    /**
     * 支付失败页面
     * Payment failure page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function failure(Request $request)
    {
        $orderId = $request->get('order_id');
        $errorMessage = $request->get('error_message', trans('security_payment::app.shop.payment.payment_failed'));
        
        $order = null;
        if ($orderId) {
            try {
                $order = $this->orderRepository->findOrFail($orderId);
            } catch (\Exception $e) {
                // Order not found
            }
        }

        return view('security_payment::failure', compact('order', 'errorMessage'));
    }

    /**
     * 支付错误页面
     * Payment error page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function error(Request $request)
    {
        $errorMessage = $request->get('error', trans('security_payment::app.shop.payment.payment_error'));
        
        return view('security_payment::error', compact('errorMessage'));
    }
}