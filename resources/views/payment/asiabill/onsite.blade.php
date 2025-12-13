@php
    /**
     * 方法说明（中文）：Asiabill 站内支付页面模板，加载JS SDK并创建支付方式
     * Purpose (English): Asiabill on-site payment template to load JS SDK and create payment method
     */
    $clientScript = $scriptTag ?? '';
@endphp

<x-shop::layouts>
    <x-slot:title>
        Asiabill Payment
    </x-slot:title>

    <div class="container py-6">
        <h1 class="text-2xl font-semibold mb-4">Asiabill On-Site Payment</h1>

        <div id="asiabill-card-element" class="mb-4"></div>

        <button id="asiabill-pay" class="btn btn-primary">Pay Now</button>

        <div id="asiabill-error" class="text-red-500 mt-3"></div>
    </div>

    @push('scripts')
        {!! $clientScript !!}

        <script>
            async function fetchSession() {
                const resp = await fetch("{{ route('asiabill.session') }}");
                const json = await resp.json();
                return json?.data || {};
            }

            (async function init() {
                const { sessionToken, customerId } = await fetchSession();
                if (!sessionToken) {
                    document.getElementById('asiabill-error').textContent = 'Failed to get sessionToken';
                    return;
                }

                if (typeof window.asiabill === 'undefined') {
                    document.getElementById('asiabill-error').textContent = 'JS SDK not loaded';
                    return;
                }

                // 初始化支付表单元素
                const element = window.asiabill.elementInit({ sessionToken });
                element.mount('#asiabill-card-element');

                document.getElementById('asiabill-pay').addEventListener('click', async () => {
                    try {
                        const pm = await window.asiabill.confirmPaymentMethod({ sessionToken });
                        if (!pm || !pm.customerPaymentMethodId) {
                            throw new Error('Payment method creation failed');
                        }

                        const body = new URLSearchParams({
                            customerPaymentMethodId: pm.customerPaymentMethodId,
                            customerId: customerId || ''
                        });

                        const resp = await fetch("{{ route('asiabill.confirm') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body
                        });

                        const json = await resp.json();
                        if (json?.success) {
                            window.location.href = "{{ route('shop.checkout.onepage.success') }}";
                        } else if (json?.data?.redirectUrl) {
                            window.location.href = json.data.redirectUrl;
                        } else {
                            document.getElementById('asiabill-error').textContent = 'Payment failed';
                        }
                    } catch (e) {
                        document.getElementById('asiabill-error').textContent = e.message || 'Error';
                    }
                });
            })();
        </script>
    @endpush
</x-shop::layouts>

