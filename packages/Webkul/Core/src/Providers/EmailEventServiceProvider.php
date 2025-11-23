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
            \Webkul\Core\Listeners\MessageSentListener::class,
        ],
        MessageFailed::class => [
            \Webkul\Core\Listeners\MessageFailedListener::class,
        ],
    ];

    
}
