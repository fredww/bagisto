<x-shop::layouts>
    <x-slot:title>
        Payment Result
    </x-slot>

    <div class="container px-[60px] max-lg:px-8 max-md:px-4">
        <div class="mx-auto mt-10 max-w-[720px] rounded-xl border border-zinc-200 bg-white p-8 shadow-sm max-md:mt-6 max-md:p-6">
            <h1 class="text-2xl font-semibold text-gray-800">Payment Return</h1>

            @if(!empty($failureCode))
                <div class="mt-4 rounded-lg bg-red-50 p-4 text-red-700">
                    <p class="font-medium">Failure Code: {{ $failureCode }}</p>
                    @if(!empty($failureMsg))
                        <p class="mt-1">Message: {{ $failureMsg }}</p>
                    @endif
                </div>
            @endif

            @if(!empty($payment))
                <div class="mt-6 grid gap-2 text-gray-700">
                    <p><span class="font-medium">Invoice:</span> {{ $payment->invoice_id }}</p>
                    <p><span class="font-medium">Order No:</span> {{ $payment->order_no }}</p>
                    <p><span class="font-medium">Status:</span> {{ $payment->status }}</p>
                </div>
            @endif

            <a
                href="{{ route('shop.checkout.cart.index') }}"
                class="primary-button mt-8 inline-block"
            >
                Back to Cart
            </a>
        </div>
    </div>
</x-shop::layouts>