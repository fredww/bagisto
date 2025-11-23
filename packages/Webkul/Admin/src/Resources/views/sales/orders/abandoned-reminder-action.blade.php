@php
    $canSend = in_array($order->status, [\Webkul\Sales\Models\Order::STATUS_PENDING, \Webkul\Sales\Models\Order::STATUS_PENDING_PAYMENT]) && ! $order->abandoned_email_sent_at;
    $template = app(\Webkul\Marketing\Repositories\AbandonedOrderTemplateRepository::class)->allActive()->first();
    $defaultSubject = $template?->subject ?? 'Complete your order';
    $defaultBody = $template?->body ?? '';

    $renderer = app(\Webkul\Marketing\Services\AbandonedTemplateRenderer::class);
    $rendered = $renderer->render($order, [
        'subject' => $defaultSubject,
        'body'    => $defaultBody,
    ]);
    $defaultSubject = $rendered['subject'] ?? $defaultSubject;
    $defaultBody = $rendered['body'] ?? $defaultBody;
@endphp

<div class="flex items-center">
    @if ($canSend)
        <x-admin::drawer>
            <x-slot:toggle>
                <button type="button" class="primary-button">发送弃单提醒</button>
            </x-slot:toggle>

            <x-slot:header>
                <p class="text-xl font-medium dark:text-white">发送弃单提醒</p>
            </x-slot:header>

            <x-slot:content>
                <x-admin::form method="POST" :action="route('admin.sales.orders.send_abandoned_reminder', $order->id)">
                    <div class="grid gap-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>邮箱</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="email" name="email" :value="$order->customer_email" rules="required|email" />
                            <x-admin::form.control-group.error control-name="email" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>标题</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" name="subject" :value="$defaultSubject" rules="required" />
                            <x-admin::form.control-group.error control-name="subject" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>内容</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="textarea" name="body" :value="$defaultBody" />
                            <x-admin::form.control-group.error control-name="body" />
                        </x-admin::form.control-group>

                        <div class="flex justify-end gap-2">
                            <button type="submit" class="primary-button">发送</button>
                        </div>
                    </div>
                </x-admin::form>
            </x-slot:content>
        </x-admin::drawer>
    @else
        <p class="text-gray-600">无</p>
    @endif
</div>
