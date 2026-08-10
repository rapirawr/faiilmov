<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminScript extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'code',
        'last_run_at',
        'last_run_output',
        'last_run_status',
        'execution_time_ms',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
        'execution_time_ms' => 'integer',
    ];
}
