<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatchPartyParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'watch_party_id',
        'user_id',
        'guest_name',
        'session_id',
        'is_host',
        'is_muted',
        'joined_at',
        'left_at',
    ];

    public function watchParty()
    {
        return $this->belongsTo(WatchParty::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }
        return $this->guest_name ?: 'Guest';
    }
}
