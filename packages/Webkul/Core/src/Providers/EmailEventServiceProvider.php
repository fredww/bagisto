<?php

namespace Webkul\Core\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageFailed;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Webkul\Core\Repositories\EmailLogRepository;

class EmailEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageSent::class => [
            'Webkul\\Core\\Providers\\EmailEventServiceProvider@handleSent',
        ],
        MessageFailed::class => [
            'Webkul\\Core\\Providers\\EmailEventServiceProvider@handleFailed',
        ],
    ];

    public function handleSent(MessageSent $event): void
    {
        $message = $event->message->getOriginalMessage();

        $to = $message->getTo();
        $subject = $message->getSubject();

        if (! empty($to)) {
            /** @var SymfonyAddress $addr */
            $addr = $to[0];
            app(EmailLogRepository::class)
                ->findQueuedByRecipientAndSubject($addr->getAddress(), $subject)?->markSent();
        }
    }

    public function handleFailed(MessageFailed $event): void
    {
        $message = $event->message->getOriginalMessage();
        $to = $message->getTo();
        $subject = $message->getSubject();

        if (! empty($to)) {
            /** @var SymfonyAddress $addr */
            $addr = $to[0];
            $log = app(EmailLogRepository::class)
                ->findQueuedByRecipientAndSubject($addr->getAddress(), $subject);

            if ($log) {
                app(EmailLogRepository::class)->markFailed($log, $event->exception?->getMessage() ?? '');
            }
        }
    }
}

