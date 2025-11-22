<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Models\OrderProxy;

class EmailLog extends Model
{
    protected $table = 'email_logs';

    protected $fillable = [
        'mailable_class',
        'category',
        'recipient_email',
        'recipient_name',
        'subject',
        'body',
        'status',
        'failure_reason',
        'retry_count',
        'sent_at',
        'context_type',
        'context_id',
        'order_id',
    ];

    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }
}

