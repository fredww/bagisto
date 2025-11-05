@if (
    request()->routeIs('shop.checkout.onepage.index')
    && (bool) core()->getConfigData('sales.payment_methods.paypal_apple_pay.active')
)
    @php
        $clientId = core()->getConfigData('sales.payment_methods.paypal_apple_pay.client_id');
        $merchantId = core()->getConfigData('sales.payment_methods.paypal_apple_pay.merchant_id');

        $acceptedCurrency = core()->getConfigData('sales.payment_methods.paypal_apple_pay.accepted_currencies');

        $currentCurrency = core()->getCurrentCurrencyCode();

        $acceptedCurrenciesArray = array_map('trim', explode(',', $acceptedCurrency));

        $currencyToUse = in_array($currentCurrency, $acceptedCurrenciesArray)
            ? $currentCurrency
            : $acceptedCurrenciesArray[0];
    @endphp

    @pushOnce('styles')
        <style>
            .apple-pay-button-container {
                min-height: 45px;
                width: 100%;
                margin-bottom: 1rem;
            }

            .apple-pay-button-container:empty {
                display: none;
            }

            .apple-pay-button {
                display: block;
                width: 100%;
                min-height: 45px;
                border-radius: 8px;
                background-color: #000;
                color: #fff;
                border: none;
                cursor: pointer;
                font-size: 16px;
                font-weight: 500;
                transition: background-color 0.2s ease;
            }

            .apple-pay-button:hover {
                background-color: #333;
            }

            .apple-pay-button:disabled {
                background-color: #ccc;
                cursor: not-allowed;
            }

            /* Apple Pay logo styling */
            .apple-pay-logo {
                display: inline-block;
                width: 20px;
                height: 20px;
                margin-right: 8px;
                vertical-align: middle;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .apple-pay-button-container {
                    min-height: 40px;
                }
                
                .apple-pay-button {
                    min-height: 40px;
                    font-size: 14px;
                }
            }
        </style>
    @endPushOnce

    @pushOnce('scripts')
        <script
            src="https://www.paypal.com/sdk/js?client-id={{ $clientId }}&currency={{ $currencyToUse }}&enable-funding=applepay"
            data-partner-attribution-id="Bagisto_Cart"
        >
        </script>

        <script
            type="text/x-template"
            id="v-apple-pay-template"
        >
            <div class="w-full">
                <!-- Apple Pay Button Container -->
                <div class="apple-pay-button-container"></div>

                <!-- Fallback Place Order Button when Apple Pay unavailable or as alternative option -->
                {{-- Chinese: 当设备/浏览器不支持Apple Pay时显示回退下单按钮，或者作为替代选项始终显示 --}}
                <div v-if="showFallback || showAlternativeButton" class="mt-3">
                    <button
                        type="button"
                        class="primary-button w-max rounded-2xl bg-navyBlue px-11 py-3 max-md:mb-4 max-md:w-full max-md:max-w-full max-md:rounded-lg max-sm:py-1.5"
                        @click="placeOrderFallback"
                        :disabled="isProcessing"
                    >
                        @lang('shop::app.checkout.onepage.summary.place-order')
                    </button>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-apple-pay', {
                template: '#v-apple-pay-template',

                data() {
                    return {
                        isProcessing: false,
                        authorizationFailed: false,
                        // Chinese: 控制是否显示回退的下单按钮（当Apple Pay不可用时）
                        showFallback: false,
                        // Chinese: 控制是否显示替代的下单按钮（当Apple Pay按钮成功渲染后显示）
                        showAlternativeButton: false,
                    };
                },

                mounted() {
                    this.initializeApplePay();
                },

                methods: {
                    initializeApplePay() {
                        if (typeof paypal === 'undefined') {
                            this.$emitter.emit('add-flash', { 
                                type: 'error', 
                                message: '@lang('paypal::app.errors.invalid-configs')' 
                            });
                            // Chinese: PayPal SDK不可用时，显示回退下单按钮
                            this.showFallback = true;
                            return;
                        }

                        // Check if Apple Pay is available
                        if (!this.isApplePayAvailable()) {
                            console.log('Apple Pay is not available on this device');
                            // Chinese: 设备不支持Apple Pay，显示回退下单按钮
                            this.showFallback = true;
                            return;
                        }

                        this.renderApplePayButton();
                    },

                    isApplePayAvailable() {
                        // Check if running in a browser that supports Apple Pay
                        if (typeof window === 'undefined') return false;

                        // Check for Apple Pay Session support
                        if (window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
                            return true;
                        }

                        // Fallback: Check if it's an Apple device
                        return /iPad|iPhone|iPod|Mac/.test(navigator.userAgent);
                    },

                    renderApplePayButton() {
                        const applePayButton = paypal.Buttons({
                            fundingSource: paypal.FUNDING.APPLEPAY,
                            
                            style: {
                                layout: 'vertical',
                                shape: 'rect',
                                color: 'black',
                                label: 'pay',
                                height: 45
                            },

                            createOrder: (data, actions) => {
                                this.isProcessing = true;
                                
                                return this.$axios.post("{{ route('paypal.apple_pay.create_order') }}", {
                                        _token: "{{ csrf_token() }}"
                                    })
                                    .then(response => {
                                        if (response.data && response.data.order_id) {
                                            return response.data.order_id;
                                        }
                                        throw new Error('Invalid order response');
                                    })
                                    .catch(error => {
                                        this.isProcessing = false;
                                        
                                        if (error.response?.data?.error === 'invalid_client') {
                                            this.authorizationFailed = true;
                                            this.showError('@lang('paypal::app.errors.invalid-configs')');
                                        } else if (error.response?.data?.message) {
                                            this.showError(error.response.data.message);
                                        } else {
                                            this.showError('@lang('paypal::app.apple-pay.errors.order-creation-failed')');
                                        }
                                        
                                        throw error;
                                    });
                            },

                            onApprove: (data, actions) => {
                                return this.$axios.post("{{ route('paypal.apple_pay.capture_order') }}", {
                                    _token: "{{ csrf_token() }}",
                                    order_id: data.orderID
                                })
                                .then(response => {
                                    this.isProcessing = false;
                                    
                                    if (response.data.success) {
                                        this.showSuccess('@lang('paypal::app.apple-pay.success.payment-completed')');
                                        
                                        setTimeout(() => {
                                            if (response.data.redirect_url) {
                                                window.location.href = response.data.redirect_url;
                                            } else {
                                                window.location.href = "{{ route('shop.checkout.onepage.success') }}";
                                            }
                                        }, 1000);
                                    } else {
                                        this.showError(response.data.message || '@lang('paypal::app.apple-pay.errors.capture-failed')');
                                    }
                                })
                                .catch(error => {
                                    this.isProcessing = false;
                                    
                                    if (error.response?.data?.message) {
                                        this.showError(error.response.data.message);
                                    } else {
                                        this.showError('@lang('paypal::app.apple-pay.errors.capture-failed')');
                                    }
                                    
                                    // Redirect to cart on error
                                    setTimeout(() => {
                                        window.location.href = "{{ route('shop.checkout.cart.index') }}";
                                    }, 2000);
                                });
                            },

                            onCancel: (data) => {
                                this.isProcessing = false;
                                console.log('Apple Pay payment was cancelled');
                            },

                            onError: (error) => {
                                this.isProcessing = false;
                                
                                if (!this.authorizationFailed) {
                                    console.error('Apple Pay error:', error);
                                    this.showError('@lang('paypal::app.apple-pay.errors.something-went-wrong')');
                                }
                                // Chinese: 渲染或处理出错时，显示回退下单按钮
                                this.showFallback = true;
                            },
                        });

                        // Check if Apple Pay button is eligible to be rendered
                        if (applePayButton.isEligible && applePayButton.isEligible()) {
                            applePayButton.render('.apple-pay-button-container')
                                .then(() => {
                                    console.log('Apple Pay button rendered successfully');
                                    // Chinese: Apple Pay按钮成功渲染后，显示备用下单按钮
                                    this.showAlternativeButton = true;
                                })
                                .catch(error => {
                                    console.error('Failed to render Apple Pay button:', error);
                                    this.showFallback = true;
                                });
                        } else {
                            console.log('Apple Pay button is not eligible for rendering');
                            this.showFallback = true;
                        }
                    },

                    // Chinese: 回退下单逻辑，模拟父组件placeOrder
                    placeOrderFallback() {
                        this.isProcessing = true;

                        this.$axios.post('{{ route('shop.checkout.onepage.orders.store') }}')
                            .then(response => {
                                const payload = response.data?.data ?? response.data;

                                if (payload?.redirect) {
                                    window.location.href = payload.redirect_url;
                                } else {
                                    window.location.href = '{{ route('shop.checkout.onepage.success') }}';
                                }
                            })
                            .catch(error => {
                                const message = error?.response?.data?.message ?? 'Order failed';
                                this.$emitter.emit('add-flash', { type: 'error', message });
                            })
                            .finally(() => {
                                this.isProcessing = false;
                            });
                    },

                    showError(message) {
                        this.$emitter.emit('add-flash', { 
                            type: 'error', 
                            message: message 
                        });
                    },

                    showSuccess(message) {
                        this.$emitter.emit('add-flash', { 
                            type: 'success', 
                            message: message 
                        });
                    },
                },
            });
        </script>
    @endPushOnce
@endif