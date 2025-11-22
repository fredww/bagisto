<?php

namespace Webkul\Shop\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Sales\Contracts\Order;
use Webkul\Shop\Mail\Mailable;

class AbandonedReminder extends Mailable
{
    public function __construct(public Order $order, public array $payload)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    $this->order->customer_email,
                    $this->order->customer_full_name
                ),
            ],
            subject: $this->payload['subject'] ?? 'Complete your order',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.orders.abandoned-reminder',
            with: [
                'order'   => $this->order,
                'payload' => $this->payload,
            ],
        );
    }
}

