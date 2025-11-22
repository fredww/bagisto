<?php

namespace Webkul\Marketing\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Core\Repositories\EmailLogRepository;
use Webkul\Marketing\Repositories\AbandonedOrderTemplateRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class AbandonedOrderReminder extends Command
{
    protected $signature = 'abandoned:reminder';

    protected $description = 'Send abandoned order reminders based on configured templates';

    public function handle(): int
    {
        $templates = app(AbandonedOrderTemplateRepository::class)->allActive();

        foreach ($templates as $template) {
            $threshold = now()->subHours($template->trigger_hours);

            $orders = app(OrderRepository::class)->scopeQuery(function ($query) use ($threshold) {
                return $query->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PENDING_PAYMENT])
                    ->where('created_at', '<=', $threshold)
                    ->orderBy('created_at', 'asc');
            })->all();

            foreach ($orders as $order) {
                $alreadySent = DB::table('email_logs')
                    ->where('category', 'abandoned_order')
                    ->where('order_id', $order->id)
                    ->whereDate('created_at', '>=', $threshold->toDateString())
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $payload = [
                    'subject' => $template->subject,
                    'body'    => $template->body,
                ];

                try {
                    $mailable = new \Webkul\Shop\Mail\Order\AbandonedReminder($order, $payload);

                    app(EmailLogRepository::class)->create([
                        'mailable_class'   => get_class($mailable),
                        'category'        => 'abandoned_order',
                        'recipient_email' => $order->customer_email,
                        'recipient_name'  => $order->customer_full_name,
                        'subject'         => $payload['subject'],
                        'body'            => $payload['body'],
                        'status'          => 'queued',
                        'context_type'    => 'order',
                        'context_id'      => $order->id,
                        'order_id'        => $order->id,
                    ]);

                    Mail::queue($mailable);

                    $this->info("Queued abandoned reminder for order #{$order->increment_id}");
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
        }

        return Command::SUCCESS;
    }
}

