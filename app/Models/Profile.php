<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'avatar',
        'is_child',
        'pin',
    ];

    protected $casts = [
        'is_child' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }
}
