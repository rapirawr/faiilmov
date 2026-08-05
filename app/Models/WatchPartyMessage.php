<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatchPartyMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'watch_party_id',
        'user_id',
        'sender_name',
        'message',
        'is_system',
    ];

    public function watchParty()
    {
        return $this->belongsTo(WatchParty::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
