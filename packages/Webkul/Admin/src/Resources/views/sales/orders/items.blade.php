<div class="flex flex-wrap gap-1.5">
    @php
        $restCount = max($order->items->count() - 3, 0);
    @endphp

    @foreach ($order->items->take(3) as $item)
        <div class="relative">
            <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded">
                @if ($item->product?->images->count() > 0)
                    <img 
                        class="h-full w-full rounded" 
                        src="{{ $item->product->base_image_url }}"
                    >

                    <span class="absolute bottom-px rounded-full bg-darkPink px-1.5 text-xs font-bold leading-normal text-white ltr:left-px rtl:right-px">
                        {{ $item->qty_ordered }}
                    </span>
                @else
                    <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert">
                        <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}">
                        
                        <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400"> 
                            @lang('admin::app.sales.invoices.view.product-image') 
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    @if ($restCount >= 1)
        <a href="{{ route('admin.sales.orders.view', $order->id) }}">
            <div class="flex h-[65px] w-[65px] items-center rounded bg-gray-50 dark:bg-gray-800">
                <p class="px-1.5 py-1.5 text-center text-xs font-bold text-gray-600 dark:text-gray-300">
                    @lang('admin::app.sales.orders.index.datagrid.product-count', ['count' => $restCount])
                </p>
            </div>
        </a>
    @endif
</div>

@php
    $templateAb = app(\Webkul\Marketing\Repositories\AbandonedOrderTemplateRepository::class)->allActive()->first();
    $defaultSubjectAb = $templateAb?->subject ?? 'Complete your order';
    $defaultBodyAb = $templateAb?->body ?? '';
    $eligible = in_array($order->status, [\Webkul\Sales\Models\Order::STATUS_PENDING, \Webkul\Sales\Models\Order::STATUS_PENDING_PAYMENT]) && ! $order->abandoned_email_sent_at;
@endphp

<div class="mt-1">
    <x-admin::drawer>
        <x-slot:toggle>
            <button type="button" class="text-sm text-blue-600 transition-all hover:underline">发送弃单提醒</button>
        </x-slot:toggle>

        <x-slot:header>
            <p class="text-xl font-medium dark:text-white">发送弃单提醒</p>
        </x-slot:header>

        <x-slot:content>
            @if (! $eligible)
                <div class="mb-2 text-sm text-gray-600">当前订单不满足发送条件（仅限待处理/待支付且未发送过）。</div>
            @endif

            <x-admin::form method="POST" :action="route('admin.sales.orders.send_abandoned_reminder', $order->id)">
                <div class="grid gap-4">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>邮箱</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="email" name="email" :value="$order->customer_email" rules="required|email" />
                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>标题</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="subject" :value="$defaultSubjectAb" rules="required" />
                        <x-admin::form.control-group.error control-name="subject" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>内容</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="textarea" name="body">{!! $defaultBodyAb !!}</x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="body" />
                    </x-admin::form.control-group>

                    <div class="flex justify-end gap-2">
                        <button type="submit" class="primary-button" @if(! $eligible) disabled @endif>发送</button>
                    </div>
                </div>
            </x-admin::form>
        </x-slot:content>
    </x-admin::drawer>
</div>
