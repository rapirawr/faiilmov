<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatchPartyReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'watch_party_id',
        'sender_name',
        'emoji',
    ];

    public function watchParty()
    {
        return $this->belongsTo(WatchParty::class);
    }
}
