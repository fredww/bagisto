<?php

namespace Webkul\AiReview\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $table = 'ai_models';

    protected $fillable = [
        'model_id', 'name', 'provider', 'api_endpoint', 'auth', 'enabled',
    ];

    protected $casts = [
        'auth' => 'array',
        'enabled' => 'boolean',
    ];
}