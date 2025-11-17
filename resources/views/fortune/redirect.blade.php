<x-shop::layouts>
    <x-slot:title>
        Redirecting to Payment
    </x-slot>

    <div class="container px-[60px] max-lg:px-8 max-md:px-4">
        <div class="mx-auto mt-10 max-w-[680px] rounded-xl border border-zinc-200 bg-white p-8 text-center shadow-sm max-md:mt-6 max-md:p-6">
            <h1 class="text-2xl font-semibold text-gray-800">Redirecting</h1>

            <p class="mt-4 text-gray-600">You will be redirected shortly. If not, click the button below.</p>

            @if(!empty($url))
                <a
                    href="{{ $url }}"
                    class="primary-button mt-6 inline-block"
                >
                    Continue to Payment
                </a>
            @else
                <p class="mt-4 text-red-600">Missing payment URL.</p>
            @endif
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var url = "{{ $url ?? '' }}";
                if (url) {
                    window.location.href = url;
                }
            });
        </script>
    @endPushOnce
</x-shop::layouts>