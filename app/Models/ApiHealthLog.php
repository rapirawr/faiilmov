<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiHealthLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'host',
        'status',
        'status_code',
        'latency_ms',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'latency_ms' => 'integer',
        'checked_at' => 'datetime',
    ];

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('checked_at', '>=', now()->subHours($hours));
    }

    public function scopeForService($query, string $service, ?string $host = null)
    {
        $query->where('service', $service);
        if ($host !== null) {
            $query->where('host', $host);
        }
        return $query;
    }
}
