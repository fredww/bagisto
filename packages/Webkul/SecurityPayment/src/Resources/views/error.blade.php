@extends('shop::layouts.master')

@section('page_title')
    {{ trans('security_payment::app.shop.payment.error_title') }}
@endsection

@section('content-wrapper')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card border-0 shadow-lg">
                    <div class="card-body text-center p-5">
                        <!-- 错误图标 Error Icon -->
                        <div class="mb-4">
                            <div class="error-icon">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="12" fill="#ffc107"/>
                                    <path d="M12 8v4M12 16h.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>

                        <!-- 错误标题 Error Title -->
                        <h1 class="h2 text-warning mb-3">
                            {{ trans('security_payment::app.shop.payment.payment_error') }}
                        </h1>

                        <!-- 错误消息 Error Message -->
                        <p class="lead text-muted mb-4">
                            {{ $errorMessage }}
                        </p>

                        <!-- 错误详情 Error Details -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ trans('security_payment::app.shop.payment.error_occurred') }}
                            </h5>
                            <p class="mb-3">{{ trans('security_payment::app.shop.payment.error_description') }}</p>
                            
                            <hr>
                            
                            <h6>{{ trans('security_payment::app.shop.payment.possible_causes') }}:</h6>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2">
                                    <i class="fas fa-circle text-warning me-2" style="font-size: 0.5rem;"></i>
                                    {{ trans('security_payment::app.shop.payment.network_issue') }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-circle text-warning me-2" style="font-size: 0.5rem;"></i>
                                    {{ trans('security_payment::app.shop.payment.gateway_maintenance') }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-circle text-warning me-2" style="font-size: 0.5rem;"></i>
                                    {{ trans('security_payment::app.shop.payment.session_expired') }}
                                </li>
                                <li>
                                    <i class="fas fa-circle text-warning me-2" style="font-size: 0.5rem;"></i>
                                    {{ trans('security_payment::app.shop.payment.invalid_request') }}
                                </li>
                            </ul>
                        </div>

                        <!-- 建议操作 Suggested Actions -->
                        <div class="alert alert-info" role="alert">
                            <h6>{{ trans('security_payment::app.shop.payment.recommended_actions') }}:</h6>
                            <ul class="list-unstyled text-start mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-check text-info me-2"></i>
                                    {{ trans('security_payment::app.shop.payment.refresh_page') }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-info me-2"></i>
                                    {{ trans('security_payment::app.shop.payment.check_connection') }}
                                </li>
                                <li>
                                    <i class="fas fa-check text-info me-2"></i>
                                    {{ trans('security_payment::app.shop.payment.try_later') }}
                                </li>
                            </ul>
                        </div>

                        <!-- 操作按钮 Action Buttons -->
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="{{ route('shop.checkout.onepage.index') }}" class="btn btn-primary">
                                <i class="fas fa-redo me-1"></i>
                                {{ trans('security_payment::app.shop.payment.try_again') }}
                            </a>
                            
                            <a href="{{ route('shop.checkout.cart.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-cart me-1"></i>
                                {{ trans('shop::app.checkout.cart.title') }}
                            </a>
                            
                            <a href="{{ route('shop.home.index') }}" class="btn btn-outline-info">
                                <i class="fas fa-home me-1"></i>
                                {{ trans('shop::app.home.title') }}
                            </a>
                        </div>

                        <!-- 客服联系信息 Customer Service Contact -->
                        <div class="mt-4 pt-4 border-top">
                            <p class="text-muted mb-2">
                                <small>{{ trans('security_payment::app.shop.payment.persistent_issue') }}</small>
                            </p>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <a href="{{ route('shop.cms.page', 'contact-us') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-headset me-1"></i>
                                    {{ trans('security_payment::app.shop.payment.contact_support') }}
                                </a>
                                <a href="mailto:support@example.com" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-envelope me-1"></i>
                                    {{ trans('security_payment::app.shop.payment.email_support') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .error-icon {
            display: inline-block;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .gap-3 {
            gap: 1rem;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
        
        .list-unstyled li {
            padding: 0.25rem 0;
        }
    </style>
@endpush