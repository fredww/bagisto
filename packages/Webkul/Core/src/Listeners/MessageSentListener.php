<?php

namespace Webkul\Core\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Webkul\Core\Repositories\EmailLogRepository;

class MessageSentListener
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message; // Symfony\Component\Mime\Email

        $to = $message->getTo();
        $subject = $message->getSubject();

        if (! empty($to)) {
            /** @var SymfonyAddress $addr */
            $addr = $to[0];
            $repo = app(EmailLogRepository::class);
            $log = $repo->findQueuedByRecipientAndSubject($addr->getAddress(), $subject);
            if ($log) {
                $repo->markSent($log);
            }
        }
    }
}
