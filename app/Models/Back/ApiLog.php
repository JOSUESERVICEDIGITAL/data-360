<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'api_name',
        'endpoint',
        'query',
        'status_code',
        'success',
        'request_data',
        'response_data',
        'error_message',
    ];

    protected $casts = [
        'success' => 'boolean',
        'request_data' => 'array',
        'response_data' => 'array',
    ];
}