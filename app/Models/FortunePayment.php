<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FortunePayment extends Model
{
    /**
     * 支付记录模型
     * Purpose: Persist and query Fortune payment lifecycle data
     */
    protected $table = 'fortune_payments';

    protected $fillable = [
        'order_no',
        'invoice_id',
        'currency',
        'amount',
        'status',
        'pay_no',
        'redirect',
        'url',
        'failure_code',
        'failure_msg',
        'client_ip',
        'client_agent',
        'client_language',
        'bn',
        'payment_method',
        'gateway_response',
        'notified_at',
        'returned_at',
    ];
}