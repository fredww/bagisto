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
            </div>
        </script>

        <script type="module">
            app.component('v-apple-pay', {
                template: '#v-apple-pay-template',

                data() {
                    return {
                        isProcessing: false,
                        authorizationFailed: false,
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
                            return;
                        }

                        // Check if Apple Pay is available
                        if (!this.isApplePayAvailable()) {
                            console.log('Apple Pay is not available on this device');
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
                                
                                return this.$axios.get("{{ route('paypal.apple-pay.create-order') }}")
                                    .then(response => {
                                        if (response.data && response.data.result) {
                                            return response.data.result.id;
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
                                return this.$axios.post("{{ route('paypal.apple-pay.capture-order') }}", {
                                    _token: "{{ csrf_token() }}",
                                    orderData: data
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
                            },
                        });

                        // Check if Apple Pay button is eligible to be rendered
                        if (applePayButton.isEligible && applePayButton.isEligible()) {
                            applePayButton.render('.apple-pay-button-container')
                                .then(() => {
                                    console.log('Apple Pay button rendered successfully');
                                })
                                .catch(error => {
                                    console.error('Failed to render Apple Pay button:', error);
                                });
                        } else {
                            console.log('Apple Pay button is not eligible for rendering');
                        }
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