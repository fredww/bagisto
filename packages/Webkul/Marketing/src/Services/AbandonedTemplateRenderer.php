<?php

namespace Webkul\Marketing\Services;

use Webkul\Sales\Contracts\Order;

class AbandonedTemplateRenderer
{
    public function render(Order $order, array $payload): array
    {
        $subject = $this->replace($payload['subject'] ?? '', $order);
        $body    = $this->replace($payload['body'] ?? '', $order);

        return [
            'subject' => $subject,
            'body'    => $body,
        ];
    }

    protected function replace(string $content, Order $order): string
    {
        $content = $this->replaceSimplePlaceholders($content, $order);
        $content = $this->replaceButtonPlaceholder($content, $order);
        $content = $this->replaceProductItemsPlaceholder($content, $order);

        return $content;
    }

    protected function replaceSimplePlaceholders(string $content, Order $order): string
    {
        $firstName = $order->customer_first_name ?: explode(' ', $order->customer_full_name)[0];
        $supportEmail = $this->safeSenderEmail();
        $storeName = $this->safeStoreName($order);

        $map = [
            '[First Name]'   => e($firstName),
            '[support email]' => e($supportEmail),
            '[Store Name]'   => e($storeName),
        ];

        return strtr($content, $map);
    }

    protected function replaceButtonPlaceholder(string $content, Order $order): string
    {
        return preg_replace_callback('/\[Button:\s*(.*?)\]/', function ($m) use ($order) {
            $label = $m[1] ?: 'Complete My Order';
            $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'shop.checkout.recover',
                now()->addHours(24),
                ['orderId' => $order->id]
            );

            $button = '<a href="'.$url.'" style="display:inline-block;padding:12px 18px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600">'.e($label).'</a>';

            return $button;
        }, $content);
    }

    protected function replaceProductItemsPlaceholder(string $content, Order $order): string
    {
        if (strpos($content, '[Product items]') === false) {
            return $content;
        }

        $itemsHtml = '<ul style="padding-left:18px;margin:0">';

        foreach ($order->items as $item) {
            $name = $item->name;
            $qty = (int) $item->qty_ordered;
            $lineTotal = $this->safeFormatPrice($item->base_total, $order);
            $itemsHtml .= '<li style="margin-bottom:6px;color:#374151">'.e($name).' × '.$qty.' — '.$lineTotal.'</li>';
        }

        $itemsHtml .= '</ul>';

        return str_replace('[Product items]', $itemsHtml, $content);
    }
    private function safeSenderEmail(): string
    {
        try {
            $email = core()->getSenderEmailDetails()['email'] ?? null;
            if ($email) return $email;
        } catch (\Throwable $e) {}

        return config('mail.from.address') ?? ('support@'.parse_url(config('app.url'), PHP_URL_HOST));
    }

    private function safeStoreName(Order $order): string
    {
        if (!empty($order->channel_name)) {
            return $order->channel_name;
        }

        $mailName = config('mail.from.name');
        if (!empty($mailName)) {
            return $mailName;
        }

        return config('app.name', 'Store');
    }

    private function safeFormatPrice($amount, Order $order): string
    {
        $currency = $order->order_currency_code ?? $order->channel_currency_code ?? $order->base_currency_code ?? config('app.currency', 'USD');
        // Bagisto stores base amounts often as float; if integer cents are used, adjust if needed.
        $value = is_int($amount) && $amount > 1000 ? ((float) $amount / 100) : (float) $amount;
        return number_format($value, 2).' '.$currency;
    }
}
