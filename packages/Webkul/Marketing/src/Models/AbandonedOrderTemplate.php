<?php

namespace Webkul\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

class AbandonedOrderTemplate extends Model
{
    protected $table = 'abandoned_order_templates';

    protected $fillable = [
        'name',
        'subject',
        'body',
        'active',
        'trigger_hours',
    ];
}

