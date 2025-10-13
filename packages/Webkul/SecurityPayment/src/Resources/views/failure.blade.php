@extends('shop::layouts.master')

@section('page_title')
    {{ trans('security_payment::app.shop.payment.failure_title') }}
@endsection

@section('content-wrapper')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card border-0 shadow-lg">
                    <div class="card-body text-center p-5">
                        <!-- 失败图标 Failure Icon -->
                        <div class="mb-4">
                            <div class="failure-icon">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="12" fill="#dc3545"/>
                                    <path d="M8 8l8 8M16 8l-8 8" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>

                        <!-- 失败标题 Failure Title -->
                        <h1 class="h2 text-danger mb-3">
                            {{ trans('security_payment::app.shop.payment.payment_failed') }}
                        </h1>

                        <!-- 错误消息 Error Message -->
                        <p class="lead text-muted mb-4">
                            {{ $errorMessage }}
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
                                        <span>{{ core()->formatPrice($order->grand_total, $order->order_currency_code) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- 建议操作 Suggested Actions -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading">{{ trans('security_payment::app.shop.payment.what_next') }}</h5>
                            <ul class="list-unstyled mb-0 text-start">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    {{ trans('security_payment::app.shop.payment.check_card_details') }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    {{ trans('security_payment::app.shop.payment.check_balance') }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    {{ trans('security_payment::app.shop.payment.try_different_card') }}
                                </li>
                                <li>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    {{ trans('security_payment::app.shop.payment.contact_bank') }}
                                </li>
                            </ul>
                        </div>

                        <!-- 操作按钮 Action Buttons -->
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="{{ route('shop.checkout.onepage.index') }}" class="btn btn-primary">
                                <i class="fas fa-redo me-1"></i>
                                {{ trans('security_payment::app.shop.payment.try_again') }}
                            </a>
                            
                            @if($order)
                                <a href="{{ route('customer.orders.view', $order->id) }}" class="btn btn-outline-info">
                                    <i class="fas fa-eye me-1"></i>
                                    {{ trans('shop::app.customer.account.order.view.title') }}
                                </a>
                            @endif
                            
                            <a href="{{ route('shop.checkout.cart.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-cart me-1"></i>
                                {{ trans('shop::app.checkout.cart.title') }}
                            </a>
                        </div>

                        <!-- 客服联系信息 Customer Service Contact -->
                        <div class="mt-4 pt-4 border-top">
                            <p class="text-muted mb-2">
                                <small>{{ trans('security_payment::app.shop.payment.need_help') }}</small>
                            </p>
                            <a href="{{ route('shop.cms.page', 'contact-us') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-headset me-1"></i>
                                {{ trans('security_payment::app.shop.payment.contact_support') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .failure-icon {
            display: inline-block;
            animation: shake 0.6s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-5px);
            }
            75% {
                transform: translateX(5px);
            }
        }
        
        .order-details {
            border-left: 4px solid #dc3545;
        }
        
        .gap-3 {
            gap: 1rem;
        }
        
        .list-unstyled li {
            padding: 0.25rem 0;
        }
    </style>
@endpush