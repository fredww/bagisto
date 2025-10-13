@extends('shop::layouts.master')

@section('page_title')
    {{ trans('security_payment::app.shop.payment.success_title') }}
@endsection

@section('content-wrapper')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card border-0 shadow-lg">
                    <div class="card-body text-center p-5">
                        <!-- 成功图标 Success Icon -->
                        <div class="mb-4">
                            <div class="success-icon">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="12" fill="#28a745"/>
                                    <path d="M8 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>

                        <!-- 成功标题 Success Title -->
                        <h1 class="h2 text-success mb-3">
                            {{ trans('security_payment::app.shop.payment.payment_successful') }}
                        </h1>

                        <!-- 成功消息 Success Message -->
                        <p class="lead text-muted mb-4">
                            {{ trans('security_payment::app.shop.payment.payment_success_message') }}
                        </p>

                        @if($order)
                            <!-- 订单信息 Order Information -->
                            <div class="order-details bg-light rounded p-4 mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>{{ trans('shop::app.customer.account.order.view.order-id') }}:</strong>
                                        <span class="text-primary">#{{ $order->increment_id }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{ trans('shop::app.customer.account.order.view.total') }}:</strong>
                                        <span class="text-success">{{ core()->formatPrice($order->grand_total, $order->order_currency_code) }}</span>
                                    </div>
                                </div>
                                
                                @if($transactionId)
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <strong>{{ trans('security_payment::app.shop.payment.transaction_id') }}:</strong>
                                            <span class="text-info">{{ $transactionId }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- 操作按钮 Action Buttons -->
                            <div class="d-flex justify-content-center gap-3">
                                <a href="{{ route('customer.orders.view', $order->id) }}" class="btn btn-primary">
                                    {{ trans('shop::app.customer.account.order.view.title') }}
                                </a>
                                <a href="{{ route('shop.home.index') }}" class="btn btn-outline-secondary">
                                    {{ trans('shop::app.home.continue-shopping') }}
                                </a>
                            </div>
                        @else
                            <!-- 无订单信息时的按钮 Buttons when no order info -->
                            <div class="d-flex justify-content-center gap-3">
                                <a href="{{ route('customer.orders.index') }}" class="btn btn-primary">
                                    {{ trans('shop::app.customer.account.order.index.title') }}
                                </a>
                                <a href="{{ route('shop.home.index') }}" class="btn btn-outline-secondary">
                                    {{ trans('shop::app.home.continue-shopping') }}
                                </a>
                            </div>
                        @endif

                        <!-- 安全提示 Security Notice -->
                        <div class="alert alert-info mt-4" role="alert">
                            <small>
                                <i class="fas fa-shield-alt me-1"></i>
                                {{ trans('security_payment::app.shop.payment.security_success_notice') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .success-icon {
            display: inline-block;
            animation: bounce 0.6s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 60%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            80% {
                transform: translateY(-5px);
            }
        }
        
        .order-details {
            border-left: 4px solid #28a745;
        }
        
        .gap-3 {
            gap: 1rem;
        }
    </style>
@endpush