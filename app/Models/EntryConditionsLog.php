<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryConditionsLog extends Model
{
    protected $fillable = [
        'filters',
        'nationality',
        'request_body',
        'response_data',
        'response_status',
        'results_count',
        'success',
        'error_message',
    ];

    protected $casts = [
        'filters' => 'array',
        'request_body' => 'array',
        'response_data' => 'array',
        'success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'filters' => '[]',
    ];
}
