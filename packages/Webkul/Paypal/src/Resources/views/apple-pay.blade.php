{{-- Apple Pay Payment Button Component --}}
<div id="apple-pay-container" class="apple-pay-container" style="display: none;">
    <div id="apple-pay-button" class="apple-pay-button"></div>
    <div id="apple-pay-error" class="apple-pay-error" style="display: none;"></div>
</div>

<script>
    // Apple Pay Handler Class - Uses PayPal JavaScript SDK correctly
    class ApplePayHandler {
        constructor() {
            this.clientId = '{{ core()->getConfigData("sales.payment_methods.paypal_apple_pay.client_id") }}';
            this.currency = '{{ core()->getCurrentCurrencyCode() }}';
            this.isEnabled = {{ core()->getConfigData('sales.payment_methods.paypal_apple_pay.active') ? 'true' : 'false' }};
            
            this.paypalButtons = null;
            this.isInitialized = false;
            
            this.init();
        }

        // Initialize Apple Pay
        async init() {
            if (!this.isEnabled) {
                console.log('Apple Pay is disabled');
                return;
            }

            try {
                await this.loadPayPalSDK();
                await this.setupApplePay();
            } catch (error) {
                console.error('Apple Pay initialization failed:', error);
                this.showError('{{ trans("paypal::app.errors.apple-pay-not-available") }}');
            }
        }

        // Load PayPal SDK with correct parameters
        loadPayPalSDK() {
            return new Promise((resolve, reject) => {
                if (window.paypal) {
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                // Use enable-funding=applepay (not components=applepay)
                // Remove merchant-id parameter - not needed for PayPal's Apple Pay integration
                script.src = `https://www.paypal.com/sdk/js?client-id=${this.clientId}&enable-funding=applepay&currency=${this.currency}`;
                script.setAttribute('data-partner-attribution-id', 'Bagisto_ApplePay');
                script.onload = resolve;
                script.onerror = () => reject(new Error('Failed to load PayPal SDK'));
                document.head.appendChild(script);
            });
        }

        // Setup Apple Pay using PayPal Buttons API (correct approach)
        async setupApplePay() {
            if (!window.paypal || !window.paypal.Buttons) {
                throw new Error('PayPal SDK not loaded correctly');
            }

            // Use PayPal Buttons API with APPLEPAY funding source
            this.paypalButtons = window.paypal.Buttons({
                fundingSource: window.paypal.FUNDING.APPLEPAY,
                
                style: {
                    layout: 'vertical',
                    shape: 'rect',
                    color: 'black',
                    label: 'pay',
                    height: 45
                },

                createOrder: (data, actions) => {
                    return this.createPayPalOrder();
                },

                onApprove: (data, actions) => {
                    return this.onApprove(data);
                },

                onCancel: (data) => {
                    this.onCancel(data);
                },

                onError: (error) => {
                    this.onError(error);
                }
            });

            // Check if Apple Pay button is eligible and render
            if (this.paypalButtons.isEligible && this.paypalButtons.isEligible()) {
                this.paypalButtons.render('#apple-pay-button')
                    .then(() => {
                        console.log('Apple Pay button rendered successfully');
                        this.showContainer();
                        this.isInitialized = true;
                    })
                    .catch(error => {
                        console.error('Failed to render Apple Pay button:', error);
                        this.showError('{{ trans("paypal::app.errors.apple-pay-not-available") }}');
                    });
            } else {
                console.log('Apple Pay button is not eligible on this device/browser');
                // Don't show error, just don't show the button
                // Apple Pay is only available on supported devices/browsers
            }
        }

        // Create PayPal order
        async createPayPalOrder() {
            try {
                this.showProcessing();
                
                const response = await fetch('{{ route("paypal.apple_pay.create_order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Failed to create PayPal order');
                }

                const result = await response.json();
                
                if (!result.success || !result.order_id) {
                    throw new Error(result.message || 'Invalid order response');
                }

                return result.order_id;
                
            } catch (error) {
                console.error('Apple Pay order creation failed:', error);
                this.showError(error.message || '{{ trans("paypal::app.errors.something-went-wrong") }}');
                this.hideProcessing();
                throw error;
            }
        }

        // Payment approval callback
        async onApprove(data) {
            try {
                this.showProcessing();
                
                const response = await fetch('{{ route("paypal.apple_pay.capture_order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        order_id: data.orderID
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Failed to capture payment');
                }

                const result = await response.json();

                if (result.success) {
                    this.showSuccess('{{ trans("paypal::app.messages.payment-successful") }}');
                    
                    // Redirect to success page
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                    } else {
                        // Fallback redirect
                        setTimeout(() => {
                            window.location.href = '{{ route("shop.checkout.onepage.success") }}';
                        }, 1000);
                    }
                } else {
                    throw new Error(result.message || 'Payment capture failed');
                }
                
            } catch (error) {
                console.error('Payment capture failed:', error);
                this.showError(error.message || '{{ trans("paypal::app.errors.capture-failed") }}');
                this.hideProcessing();
                
                // Redirect to cart on critical error
                setTimeout(() => {
                    window.location.href = '{{ route("shop.checkout.cart.index") }}';
                }, 2000);
            }
        }

        // Payment cancellation callback
        onCancel(data) {
            console.log('Apple Pay payment was cancelled by user');
            this.hideProcessing();
        }

        // Payment error callback
        onError(error) {
            console.error('Apple Pay error:', error);
            this.showError('{{ trans("paypal::app.errors.something-went-wrong") }}');
            this.hideProcessing();
        }

        // Show container
        showContainer() {
            const container = document.getElementById('apple-pay-container');
            if (container) {
                container.style.display = 'block';
            }
        }

        // Show error message
        showError(message) {
            const errorDiv = document.getElementById('apple-pay-error');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
                
                // Auto-hide error after 5 seconds
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 5000);
            }
        }

        // Show processing state
        showProcessing() {
            const button = document.getElementById('apple-pay-button');
            if (button) {
                button.style.opacity = '0.6';
                button.style.pointerEvents = 'none';
            }
        }

        // Hide processing state
        hideProcessing() {
            const button = document.getElementById('apple-pay-button');
            if (button) {
                button.style.opacity = '1';
                button.style.pointerEvents = 'auto';
            }
        }

        // Show success message
        showSuccess(message) {
            console.log('Payment successful:', message);
            // Success is handled via redirect in onApprove
        }
    }

    // Initialize Apple Pay when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.applePayHandler === 'undefined') {
                window.applePayHandler = new ApplePayHandler();
            }
        });
    } else {
        // DOM already loaded
        if (typeof window.applePayHandler === 'undefined') {
            window.applePayHandler = new ApplePayHandler();
        }
    }
</script>

<style>
.apple-pay-container {
    margin: 15px 0;
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #f9f9f9;
}

.apple-pay-button {
    width: 100%;
    min-height: 45px;
    border-radius: 6px;
}

.apple-pay-error {
    color: #d32f2f;
    font-size: 14px;
    margin-top: 10px;
    padding: 8px;
    background-color: #ffebee;
    border: 1px solid #ffcdd2;
    border-radius: 4px;
    word-wrap: break-word;
}

@media (max-width: 768px) {
    .apple-pay-container {
        margin: 10px 0;
        padding: 8px;
    }
    
    .apple-pay-button {
        min-height: 40px;
    }
}
</style>