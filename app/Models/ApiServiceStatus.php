<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiServiceStatus extends Model
{
    use HasFactory;

    protected $table = 'api_service_status';

    protected $fillable = [
        'service',
        'host',
        'current_status',
        'consecutive_failures',
        'uptime_24h',
        'avg_latency_ms',
        'last_success_at',
        'last_failure_at',
        'last_checked_at',
    ];

    protected $casts = [
        'consecutive_failures' => 'integer',
        'uptime_24h' => 'float',
        'avg_latency_ms' => 'integer',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function isUp(): bool
    {
        return $this->current_status === 'up';
    }

    public function isDegraded(): bool
    {
        return $this->current_status === 'degraded';
    }

    public function isDown(): bool
    {
        return $this->current_status === 'down';
    }
}
