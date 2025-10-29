@if (
    request()->routeIs('shop.checkout.onepage.index')
    && (bool) core()->getConfigData('sales.payment_methods.paypal_smart_button.active')
)
    @php
        $clientId = core()->getConfigData('sales.payment_methods.paypal_smart_button.client_id');

        $acceptedCurrency = core()->getConfigData('sales.payment_methods.paypal_smart_button.accepted_currencies');

        $currentCurrency = core()->getCurrentCurrencyCode();

        $acceptedCurrenciesArray = array_map('trim', explode(',', $acceptedCurrency));

        $currencyToUse = in_array($currentCurrency, $acceptedCurrenciesArray)
            ? $currentCurrency
            : $acceptedCurrenciesArray[0];

        // Apple Pay configuration
        $enableApplePay = core()->getConfigData('sales.payment_methods.paypal_smart_button.enable_apple_pay');
        $applePayEnabled = !empty($enableApplePay) ? (bool)$enableApplePay : false;
    @endphp

    @pushOnce('styles')
        @if ($applePayEnabled)
            <style>
                .paypal-applepay-button-container {
                    min-height: 45px;
                    width: 100%;
                }

                .paypal-applepay-button-container:empty {
                    display: none;
                }

                /* Ensure proper spacing between Apple Pay and regular PayPal buttons */
                .paypal-button-container {
                    margin-top: 0.5rem;
                }

                /* Apple Pay button specific styling */
                .paypal-applepay-button-container .apple-pay-button {
                    display: block;
                    width: 100%;
                    min-height: 45px;
                    border-radius: 4px;
                }

                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .paypal-applepay-button-container {
                        min-height: 40px;
                    }
                }
            </style>
        @endif
    @endPushOnce

    @pushOnce('scripts')
        <script
            src="https://www.paypal.com/sdk/js?client-id={{ $clientId }}&currency={{ $currencyToUse }}{{ $applePayEnabled ? '&enable-funding=applepay' : '' }}"
            data-partner-attribution-id="Bagisto_Cart"
        >
        </script>

        <script
            type="text/x-template"
            id="v-paypal-smart-button-template"
        >
            <div class="w-full">
                @if ($applePayEnabled)
                    <!-- Apple Pay Button Container -->
                    <div class="paypal-applepay-button-container mb-3" v-show="showApplePayButton"></div>
                @endif

                <!-- Standard PayPal Button Container -->
                <div class="paypal-button-container"></div>
            </div>
        </script>

        <script type="module">
            app.component('v-paypal-smart-button', {
                template: '#v-paypal-smart-button-template',

                data() {
                    return {
                        showApplePayButton: false,
                        authorizationFailed: false,
                    };
                },

                mounted() {
                    this.register();
                    this.checkApplePayAvailability();
                },

                methods: {
                    register() {
                        if (typeof paypal == 'undefined') {
                            this.$emitter.emit('add-flash', { type: 'error', message: '@lang('paypal::app.errors.invalid-configs')' });

                            return;
                        }

                        @if ($applePayEnabled)
                            // Render Apple Pay button if enabled and available
                            if (this.isApplePayAvailable()) {
                                this.renderApplePayButton();
                            }
                        @endif

                        // Render standard PayPal buttons
                        this.renderPayPalButton();
                    },

                    checkApplePayAvailability() {
                        @if ($applePayEnabled)
                            this.showApplePayButton = this.isApplePayAvailable();
                        @endif
                    },

                    isApplePayAvailable() {
                        // Check if Apple Pay is available on the device
                        if (typeof window === 'undefined') return false;

                        return (
                            (window.ApplePaySession && window.ApplePaySession.canMakePayments()) ||
                            // Fallback: Check if it's an Apple device
                            (/iPad|iPhone|iPod/.test(navigator.userAgent))
                        );
                    },

                    renderApplePayButton() {
                        if (!this.isApplePayAvailable()) return;

                        const applePayOptions = this.getApplePayOptions();
                        if (applePayOptions) {
                            paypal.Buttons({
                                ...applePayOptions,
                                fundingSource: 'applepay'
                            }).render('.paypal-applepay-button-container');
                        }
                    },

                    renderPayPalButton() {
                        const standardOptions = this.getStandardPayPalOptions();
                        if (standardOptions) {
                            paypal.Buttons(standardOptions).render('.paypal-button-container');
                        }
                    },

                    getApplePayOptions() {
                        return {
                            style: {
                                layout: 'vertical',
                                shape: 'rect',
                                color: 'black',
                                label: 'pay'
                            },

                            alertBox: (message) => {
                                this.$emitter.emit('add-flash', { type: 'error', message: message });
                            },

                            createOrder: (data, actions) => {
                                return this.$axios.get("{{ route('paypal.smart-button.create-order') }}")
                                    .then(response => response.data.result)
                                    .then(order => order.id)
                                    .catch(error => {
                                        if (error.response?.data?.error === 'invalid_client') {
                                            this.authorizationFailed = true;
                                            this.alertBox('@lang('paypal::app.errors.invalid-configs')');
                                        }
                                        throw error;
                                    });
                            },

                            onApprove: (data, actions) => {
                                this.$axios.post("{{ route('paypal.smart-button.capture-order') }}", {
                                    _token: "{{ csrf_token() }}",
                                    orderData: data
                                })
                                .then(response => {
                                    if (response.data.success) {
                                        if (response.data.redirect_url) {
                                            window.location.href = response.data.redirect_url;
                                        } else {
                                            window.location.href = "{{ route('shop.checkout.onepage.success') }}";
                                        }
                                    }
                                })
                                .catch(error => window.location.href = "{{ route('shop.checkout.cart.index') }}");
                            },

                            onError: (error) => {
                                if (!this.authorizationFailed) {
                                    this.alertBox('@lang('paypal::app.errors.something-went-wrong')');
                                }
                            },
                        };
                    },

                    getStandardPayPalOptions() {
                        return {
                            style: {
                                layout: 'vertical',
                                shape: 'rect',
                            },

                            fundingSource: undefined, // Show all available funding sources except Apple Pay

                            alertBox: (message) => {
                                this.$emitter.emit('add-flash', { type: 'error', message: message });
                            },

                            createOrder: (data, actions) => {
                                return this.$axios.get("{{ route('paypal.smart-button.create-order') }}")
                                    .then(response => response.data.result)
                                    .then(order => order.id)
                                    .catch(error => {
                                        if (error.response?.data?.error === 'invalid_client') {
                                            this.authorizationFailed = true;
                                            this.alertBox('@lang('paypal::app.errors.invalid-configs')');
                                        }
                                        throw error;
                                    });
                            },

                            onApprove: (data, actions) => {
                                this.$axios.post("{{ route('paypal.smart-button.capture-order') }}", {
                                    _token: "{{ csrf_token() }}",
                                    orderData: data
                                })
                                .then(response => {
                                    if (response.data.success) {
                                        if (response.data.redirect_url) {
                                            window.location.href = response.data.redirect_url;
                                        } else {
                                            window.location.href = "{{ route('shop.checkout.onepage.success') }}";
                                        }
                                    }
                                })
                                .catch(error => window.location.href = "{{ route('shop.checkout.cart.index') }}");
                            },

                            onError: (error) => {
                                if (!this.authorizationFailed) {
                                    this.alertBox('@lang('paypal::app.errors.something-went-wrong')');
                                }
                            },
                        };
                    },

                },
            });
        </script>
    @endPushOnce
@endif
