<x-shop::layouts
	:has-header="true"
	:has-feature="false"
	:has-footer="true"
>
    <!-- Page Title -->
    <x-slot:title>
		@lang('shop::app.checkout.success.thanks')
    </x-slot>

	<!-- Page content -->
	<div class="container mt-8 px-[60px] max-lg:px-8">
		<div class="grid place-items-center gap-y-5 max-md:gap-y-2.5">
			{{ view_render_event('bagisto.shop.checkout.success.image.before', ['order' => $order]) }}

			<img
				class="max-md:h-[100px] max-md:w-[100px]"
				src="{{ bagisto_asset('images/thank-you.png') }}"
				alt="@lang('shop::app.checkout.success.thanks')"
				title="@lang('shop::app.checkout.success.thanks')"
                loading="lazy"
                decoding="async"
			>

			{{ view_render_event('bagisto.shop.checkout.success.image.after', ['order' => $order]) }}

			<p class="text-xl max-md:text-sm">
				@if (auth()->guard('customer')->user())
					@lang('shop::app.checkout.success.order-id-info', [
						'order_id' => '<a class="text-blue-700" href="'.route('shop.customers.account.orders.view', $order->id).'">'.$order->increment_id.'</a>'
					])
				@else
					@lang('shop::app.checkout.success.order-id-info', ['order_id' => $order->increment_id])
				@endif
			</p>

			<p class="font-medium md:text-2xl">
				@lang('shop::app.checkout.success.thanks')
			</p>

			<p class="text-xl text-zinc-500 max-md:text-center max-md:text-xs">
				@if (! empty($order->checkout_message))
					{!! nl2br($order->checkout_message) !!}
				@else
					@lang('shop::app.checkout.success.info')
				@endif
			</p>

			{{ view_render_event('bagisto.shop.checkout.success.continue-shopping.before', ['order' => $order]) }}

			<a href="{{ route('shop.home.index') }}">
				<div class="w-max cursor-pointer rounded-2xl bg-navyBlue px-11 py-3 text-center text-base font-medium text-white max-md:rounded-lg max-md:px-6 max-md:py-1.5">
             		@lang('shop::app.checkout.cart.index.continue-shopping')
				</div>
			</a>

			{{ view_render_event('bagisto.shop.checkout.success.continue-shopping.after', ['order' => $order]) }}
		</div>
	</div>

    <!-- Inject GA4 and Google Ads purchase conversion events -->
    <script>
        (function () {
            try {
                // internal: build payloads from server-side order data
                var orderId  = "{{ addslashes($order->increment_id) }}";
                var currency = "{{ $order->order_currency_code ?? (core()->getCurrentCurrency()?->code ?? 'USD') }}";
                var value    = Number("{{ $order->grand_total ?? 0 }}");

                @php
                    $gaItems = [];
                    foreach ($order->items as $itm) {
                        $gaItems[] = [
                            'sku'   => $itm->sku ?? $itm->product_id ?? null,
                            'name'  => $itm->name ?? '',
                            'qty'   => $itm->qty_ordered ?? $itm->quantity ?? 1,
                            'price' => (float) ($itm->price ?? ($itm->total ?? 0)),
                            'total' => (float) ($itm->total ?? 0),
                        ];
                    }
                @endphp

                var itemsRaw = @json($gaItems);
                var items = itemsRaw.map(function (i) {
                    return {
                        item_id: i.sku || undefined,
                        item_name: i.name || '',
                        quantity: Number(i.qty || 1),
                        price: Number((i.price ?? i.total) || 0),
                    };
                });

                // GA4 purchase event
                if (window.GAIntegration && typeof window.GAIntegration.trackPurchase === 'function') {
                    window.GAIntegration.trackPurchase({
                        currency: currency,
                        value: value,
                        items: items,
                        transactionId: orderId,
                    });
                } else {
                    // Fallback: call gtag directly if plugin is unavailable
                    if (window.gtag) {
                        gtag('event', 'purchase', {
                            currency: currency,
                            value: value,
                            items: items,
                            transaction_id: orderId,
                        });
                    }
                }

                // Google Ads conversion
                if (window.GAIntegration && typeof window.GAIntegration.trackAdsPurchase === 'function') {
                    window.GAIntegration.trackAdsPurchase({ currency: currency, value: value });
                }

                if (window.GAIntegration && window.GAIntegration.debugLog) {
                    window.GAIntegration.debugLog('success.purchase emitted', { orderId, currency, value, items });
                }
            } catch (e) {
                if (window.GAIntegration && window.GAIntegration.debugLog) {
                    window.GAIntegration.debugLog('success.purchase hook error', e);
                }
            }
        })();
    </script>
</x-shop::layouts>
