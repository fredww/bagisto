<x-shop::layouts>
    <x-slot:title>
        Payment Error
    </x-slot>

    <div class="container px-[60px] max-lg:px-8 max-md:px-4">
        <div class="mx-auto mt-10 max-w-[680px] rounded-xl border border-zinc-200 bg-white p-8 text-center shadow-sm max-md:mt-6 max-md:p-6">
            <h1 class="text-2xl font-semibold text-red-600">Payment Error</h1>

            <p class="mt-4 text-gray-600">{{ $message ?? 'Unable to start payment.' }}</p>

            <a
                href="{{ route('shop.checkout.cart.index') }}"
                class="primary-button mt-6 inline-block"
            >
                Back to Cart
            </a>
        </div>
    </div>
</x-shop::layouts>