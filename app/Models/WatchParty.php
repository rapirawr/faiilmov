<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WatchParty extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_code',
        'film_id',
        'season_number',
        'episode_number',
        'host_user_id',
        'host_guest_name',
        'status',
        'current_position_seconds',
        'is_playing',
        'playback_speed',
        'is_locked',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($watchParty) {
            if (empty($watchParty->room_code)) {
                $watchParty->room_code = static::generateUniqueRoomCode();
            }
        });
    }

    public static function generateUniqueRoomCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::where('room_code', $code)->exists());

        return $code;
    }

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function hostUser()
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function participants()
    {
        return $this->hasMany(WatchPartyParticipant::class)->whereNull('left_at');
    }
}
