@component('shop::emails.layout')
    <div style="margin-bottom: 34px;">
        <span style="font-size: 22px;font-weight: 600;color: #121A26">
            {{ $payload['subject'] ?? 'We saved your order' }}
        </span> <br>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            {{ __('Hello') }}, {{ $order->customer_full_name }}
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            {!! $payload['body'] ?? ('Your order #' . $order->increment_id . ' is waiting. Total: ' . core()->formatBasePrice($order->base_grand_total)) !!}
        </p>
    </div>
@endcomponent

