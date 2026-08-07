<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar', 'bio', 'phone', 'is_admin', 'is_banned', 'banned_reason', 'banned_until'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_banned' => 'boolean',
            'banned_until' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isBanned(): bool
    {
        if (!$this->is_banned) {
            return false;
        }

        if ($this->banned_until && now()->greaterThan($this->banned_until)) {
            $this->update([
                'is_banned' => false,
                'banned_reason' => null,
                'banned_until' => null,
            ]);
            return false;
        }

        return true;
    }

    public function activityLogs()
    {
        return $this->hasMany(AdminActivityLog::class, 'admin_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }

    public function watchlistFilms()
    {
        return $this->belongsToMany(Film::class, 'watchlists')->withPivot('status')->withTimestamps();
    }

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }
}
