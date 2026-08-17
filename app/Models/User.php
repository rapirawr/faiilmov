<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar', 'bio', 'phone', 'is_admin', 'is_ad_free', 'is_banned', 'banned_reason', 'banned_until', 'parental_pin', 'max_allowed_rating', 'has_seen_welcome_modal', 'provider', 'provider_id', 'email_verified_at', 'last_active_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_ad_free' => 'boolean',
            'is_banned' => 'boolean',
            'banned_until' => 'datetime',
            'has_seen_welcome_modal' => 'boolean',
        ];
    }

    public function scopeActiveToday($query)
    {
        return $query->where('last_active_at', '>=', now()->startOfDay());
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isAdFree(): bool
    {
        return (bool) $this->is_ad_free || $this->isAdmin();
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

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function filmRequests()
    {
        return $this->belongsToMany(FilmRequest::class, 'film_request_user')->withTimestamps();
    }

    public function collections()
    {
        return $this->hasMany(Collection::class, 'created_by');
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }

    public function activeProfile()
    {
        $profileId = session('active_profile_id');
        if ($profileId) {
            return $this->profiles()->find($profileId);
        }
        return null;
    }

    /**
     * Get resolved avatar URL attribute
     */
    public function getAvatarUrlAttribute(): string
    {
        $val = trim($this->avatar ?? '');

        if (!empty($val) && (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, 'data:image/'))) {
            return $val;
        }

        if (!empty($val) && str_starts_with($val, 'storage/')) {
            return asset($val);
        }

        if (!empty($val) && str_starts_with($val, 'avatars/')) {
            return asset('storage/' . $val);
        }

        $seed = urlencode($this->name ?: 'User');
        return "https://api.dicebear.com/7.x/avataaars/svg?seed={$seed}";
    }

    /**
     * Send the password reset notification using our branded Faiilmov template.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
