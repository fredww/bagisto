{{-- Apple Pay 支付按钮组件 --}}
<div id="apple-pay-container" class="apple-pay-container" style="display: none;">
    <div id="apple-pay-button" class="apple-pay-button"></div>
    <div id="apple-pay-error" class="apple-pay-error" style="display: none;"></div>
</div>

<script>
    // Apple Pay 支付处理类
    class ApplePayHandler {
        constructor() {
            this.clientId = '{{ core()->getConfigData("sales.payment_methods.paypal_apple_pay.client_id") }}';
            this.merchantId = '{{ core()->getConfigData("sales.payment_methods.paypal_apple_pay.merchant_id") }}';
            this.environment = '{{ core()->getConfigData("sales.payment_methods.paypal_apple_pay.sandbox") ? "sandbox" : "production" }}';
            this.currency = '{{ core()->getCurrentCurrencyCode() }}';
            this.countryCode = '{{ core()->getConfigData("sales.shipping.origin.country") }}';
            this.isEnabled = {{ core()->getConfigData('sales.payment_methods.paypal_apple_pay.active') ? 'true' : 'false' }};
            
            this.paypalScript = null;
            this.applePayComponent = null;
            
            this.init();
        }

        // 初始化Apple Pay
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

        // 加载PayPal SDK
        loadPayPalSDK() {
            return new Promise((resolve, reject) => {
                if (window.paypal) {
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                script.src = `https://www.paypal.com/sdk/js?client-id=${this.clientId}&components=applepay&currency=${this.currency}&merchant-id=${this.merchantId}`;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        // 设置Apple Pay
        async setupApplePay() {
            if (!window.paypal || !window.paypal.Applepay) {
                throw new Error('PayPal Apple Pay SDK not loaded');
            }

            // 检查Apple Pay可用性
            const isApplePayAvailable = await window.paypal.Applepay().config({
                countryCode: this.countryCode,
            });

            if (!isApplePayAvailable) {
                throw new Error('Apple Pay not available');
            }

            // 创建Apple Pay组件
            this.applePayComponent = window.paypal.Applepay({
                style: {
                    type: 'buy',
                    color: 'black',
                    locale: 'en',
                    height: 44
                }
            });

            // 检查是否符合条件
            if (this.applePayComponent.isEligible()) {
                this.renderApplePayButton();
                this.showContainer();
            } else {
                throw new Error('Apple Pay not eligible');
            }
        }

        // 渲染Apple Pay按钮
        renderApplePayButton() {
            const buttonContainer = document.getElementById('apple-pay-button');
            if (!buttonContainer) return;

            this.applePayComponent.render('#apple-pay-button').then(() => {
                console.log('Apple Pay button rendered successfully');
                
                // 绑定点击事件
                this.applePayComponent.onClick(() => {
                    this.handleApplePayClick();
                });
            }).catch(error => {
                console.error('Failed to render Apple Pay button:', error);
                this.showError('{{ trans("paypal::app.errors.apple-pay-not-available") }}');
            });
        }

        // 处理Apple Pay点击事件
        async handleApplePayClick() {
            try {
                this.showProcessing();
                
                // 获取购物车数据
                const cartData = await this.getCartData();
                
                // 创建PayPal订单
                const orderData = await this.createPayPalOrder(cartData);
                
                // 启动Apple Pay流程
                await this.applePayComponent.confirmOrder({
                    orderId: orderData.id,
                    onApprove: (data) => this.onApprove(data),
                    onCancel: (data) => this.onCancel(data),
                    onError: (error) => this.onError(error)
                });
                
            } catch (error) {
                console.error('Apple Pay payment failed:', error);
                this.showError('{{ trans("paypal::app.errors.something-went-wrong") }}');
                this.hideProcessing();
            }
        }

        // 获取购物车数据
        async getCartData() {
            const response = await fetch('{{ route("paypal.apple_pay.cart") }}', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to get cart data');
            }

            return await response.json();
        }

        // 创建PayPal订单
        async createPayPalOrder(cartData) {
            const response = await fetch('{{ route("paypal.apple_pay.create_order") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(cartData)
            });

            if (!response.ok) {
                throw new Error('Failed to create PayPal order');
            }

            return await response.json();
        }

        // 支付批准回调
        async onApprove(data) {
            try {
                // 捕获支付
                const response = await fetch('{{ route("paypal.apple_pay.capture_order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        orderID: data.orderID
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess('{{ trans("paypal::app.messages.payment-successful") }}');
                    // 重定向到成功页面
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                    }
                } else {
                    throw new Error(result.message || 'Payment capture failed');
                }
            } catch (error) {
                console.error('Payment capture failed:', error);
                this.showError('{{ trans("paypal::app.errors.capture-failed") }}');
            } finally {
                this.hideProcessing();
            }
        }

        // 支付取消回调
        onCancel(data) {
            console.log('Apple Pay cancelled:', data);
            this.hideProcessing();
        }

        // 支付错误回调
        onError(error) {
            console.error('Apple Pay error:', error);
            this.showError('{{ trans("paypal::app.errors.something-went-wrong") }}');
            this.hideProcessing();
        }

        // 显示容器
        showContainer() {
            const container = document.getElementById('apple-pay-container');
            if (container) {
                container.style.display = 'block';
            }
        }

        // 显示错误信息
        showError(message) {
            const errorDiv = document.getElementById('apple-pay-error');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }

        // 显示处理中状态
        showProcessing() {
            const button = document.getElementById('apple-pay-button');
            if (button) {
                button.style.opacity = '0.6';
                button.style.pointerEvents = 'none';
            }
        }

        // 隐藏处理中状态
        hideProcessing() {
            const button = document.getElementById('apple-pay-button');
            if (button) {
                button.style.opacity = '1';
                button.style.pointerEvents = 'auto';
            }
        }

        // 显示成功信息
        showSuccess(message) {
            // 可以显示成功提示或直接重定向
            console.log('Payment successful:', message);
        }
    }

    // 页面加载完成后初始化Apple Pay
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.applePayHandler === 'undefined') {
            window.applePayHandler = new ApplePayHandler();
        }
    });
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
    min-height: 44px;
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
}

@media (max-width: 768px) {
    .apple-pay-container {
        margin: 10px 0;
        padding: 8px;
    }
}
</style>