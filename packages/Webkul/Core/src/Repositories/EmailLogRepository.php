<?php

namespace Webkul\Core\Repositories;

use Illuminate\Support\Arr;
use Webkul\Core\Models\EmailLog;

class EmailLogRepository
{
    public function create(array $data): EmailLog
    {
        return EmailLog::query()->create($data);
    }

    public function update(EmailLog $log, array $data): EmailLog
    {
        $log->fill($data)->save();

        return $log;
    }

    public function markSent(EmailLog $log): EmailLog
    {
        $log->status = 'sent';
        $log->sent_at = now();
        $log->save();

        return $log;
    }

    public function markFailed(EmailLog $log, string $reason): EmailLog
    {
        $log->status = 'failed';
        $log->failure_reason = $reason;
        $log->save();

        return $log;
    }

    public function incrementRetry(EmailLog $log): EmailLog
    {
        $log->retry_count = ($log->retry_count ?? 0) + 1;
        $log->save();

        return $log;
    }

    public function findQueuedByRecipientAndSubject(string $recipientEmail, ?string $subject): ?EmailLog
    {
        return EmailLog::query()
            ->where('recipient_email', $recipientEmail)
            ->when($subject, fn ($q) => $q->where('subject', $subject))
            ->whereIn('status', ['queued', 'failed'])
            ->orderByDesc('id')
            ->first();
    }

    public function deriveCategoryFromClass(?string $class): ?string
    {
        if (! $class) {
            return null;
        }

        $map = [
            'ShippedNotification'   => 'shipping',
            'InvoicedNotification'  => 'payment',
            'CreatedNotification'   => 'order',
            'CanceledNotification'  => 'order',
            'RefundedNotification'  => 'refund',
            'NoteNotification'      => 'customer',
            'EmailVerification'     => 'customer',
            'SubscriptionNotification' => 'marketing',
            'AbandonedReminder'     => 'abandoned_order',
        ];

        foreach ($map as $suffix => $category) {
            if (str_ends_with($class, $suffix)) {
                return $category;
            }
        }

        return null;
    }
}

