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

    /**
     * Get resolved avatar URL attribute
     */
    public function getAvatarUrlAttribute(): string
    {
        $val = trim($this->avatar ?? '');

        if (!empty($val)) {
            if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, 'data:image/')) {
                return $val;
            }

            if (str_starts_with($val, '/storage/')) {
                return asset(ltrim($val, '/'));
            }

            if (str_starts_with($val, 'storage/')) {
                return asset($val);
            }

            if (str_starts_with($val, '/avatars/')) {
                return asset('storage' . $val);
            }

            if (str_starts_with($val, 'avatars/')) {
                return asset('storage/' . $val);
            }
        }

        $seed = urlencode($this->name ?: 'Profile');
        return "https://api.dicebear.com/7.x/avataaars/svg?seed={$seed}";
    }
}
