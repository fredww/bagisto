<?php

namespace Webkul\AiReview\Models;

use Illuminate\Database\Eloquent\Model;

class AiReviewJob extends Model
{
    protected $table = 'ai_review_jobs';

    protected $fillable = [
        'ai_model_id', 'total_products', 'processed_products', 'status', 'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}