<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DxmOrderExport extends Model
{
    protected $table = 'dxm_order_exports';

    protected $fillable = [
        'export_date',
        'state',
        'total_rows',
        'processed_rows',
        'file_path',
        'error_message',
        'started_at',
        'completed_at',
    ];
}