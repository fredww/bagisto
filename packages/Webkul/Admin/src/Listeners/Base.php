<?php

namespace Webkul\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Sales\Contracts\OrderComment;
use Webkul\Core\Repositories\EmailLogRepository;

class Base
{
    /**
     * Get the locale of the customer if somehow item name changes then the english locale will pe provided.
     *
     * @param object \Webkul\Sales\Contracts\Order|\Webkul\Sales\Contracts\Invoice|\Webkul\Sales\Contracts\Refund|\Webkul\Sales\Contracts\Shipment|\Webkul\Sales\Contracts\OrderComment
     * @return string
     */
    protected function getLocale($object)
    {
        if ($object instanceof OrderComment) {
            $object = $object->order;
        }

        $objectFirstItem = $object->items->first();

        return $objectFirstItem->additional['locale'] ?? 'en';
    }

    /**
     * Prepare mail.
     *
     * @return void
     */
    protected function prepareMail($entity, $notification)
    {
        $customerLocale = $this->getLocale($entity);

        $previousLocale = core()->getCurrentLocale()->code;

        app()->setLocale($customerLocale);

        try {
            try {
                $envelope = method_exists($notification, 'envelope') ? $notification->envelope() : null;
                $to = $envelope?->to[0] ?? null;
                $subject = $envelope?->subject ?? null;

                $contextType = method_exists($entity, 'getMorphClass') ? $entity->getMorphClass() : (is_object($entity) ? get_class($entity) : null);
                $contextId = $entity->id ?? ($entity->order_id ?? null);
                $orderId = $entity->order_id ?? ($entity->order->id ?? null);

                app(EmailLogRepository::class)->create([
                    'mailable_class'   => is_object($notification) ? get_class($notification) : null,
                    'category'        => app(EmailLogRepository::class)->deriveCategoryFromClass(is_object($notification) ? get_class($notification) : null),
                    'recipient_email' => $to?->address ?? ($entity->order->customer_email ?? null),
                    'recipient_name'  => $to?->name ?? ($entity->order->customer_full_name ?? null),
                    'subject'         => $subject,
                    'status'          => 'queued',
                    'context_type'    => $contextType,
                    'context_id'      => $contextId,
                    'order_id'        => $orderId,
                ]);
            } catch (\Exception $e) {}

            Mail::queue($notification);
        } catch (\Exception $e) {
            \Log::error('Error in Sending Email'.$e->getMessage());
        }

        app()->setLocale($previousLocale);
    }
}
