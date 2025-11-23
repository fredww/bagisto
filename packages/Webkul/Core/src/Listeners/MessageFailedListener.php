<?php

namespace Webkul\Core\Listeners;

use Illuminate\Mail\Events\MessageFailed;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Webkul\Core\Repositories\EmailLogRepository;

class MessageFailedListener
{
    public function handle(MessageFailed $event): void
    {
        $message = $event->message; // Symfony\Component\Mime\Email
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
