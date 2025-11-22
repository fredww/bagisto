<?php

namespace Webkul\AiReview\Models;

use Illuminate\Database\Eloquent\Model;

class AiReviewLog extends Model
{
    protected $table = 'ai_review_logs';

    protected $fillable = [
        'job_id', 'product_id', 'status', 'message', 'created_count',
    ];
}