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

#[Fillable(['name', 'email', 'password', 'avatar', 'bio', 'phone', 'role', 'is_admin', 'is_ad_free', 'is_banned', 'banned_reason', 'banned_until', 'parental_pin', 'max_allowed_rating', 'has_seen_welcome_modal', 'provider', 'provider_id', 'email_verified_at', 'last_active_at', 'xp_total', 'current_level', 'streak_count', 'last_watch_date', 'is_anonymous_leaderboard'])]
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

    /**
     * Check if user is either an Admin or an Administrator.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'administrator', 'superadmin'], true) || (bool) $this->is_admin;
    }

    /**
     * Check if user is a full Administrator (Superadmin).
     */
    public function isAdministrator(): bool
    {
        return in_array($this->role, ['administrator', 'superadmin'], true) 
            || ((bool) $this->is_admin && $this->role !== 'admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdministrator();
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        if ($this->isAdministrator()) {
            return true;
        }
        return in_array($this->role, $roles, true);
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'administrator', 'superadmin' => 'Administrator',
            'admin' => 'Admin',
            default => 'Pengguna',
        };
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

    public function episodeComments()
    {
        return $this->hasMany(EpisodeComment::class);
    }

    public function episodeCommentLikes()
    {
        return $this->hasMany(EpisodeCommentLike::class);
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

    public function xpLogs()
    {
        return $this->hasMany(UserXpLog::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('unlocked_at');
    }

    /**
     * Get computed level info array
     */
    public function getLevelInfoAttribute(): array
    {
        return app(\App\Services\GamificationService::class)->calculateLevelInfo((int)($this->xp_total ?? 0));
    }

    /**
     * Get tier title (e.g. Cinephile Buff)
     */
    public function getLevelTitleAttribute(): string
    {
        return $this->level_info['title'] ?? 'Film Novice';
    }

    /**
     * Get tier Lucide icon name
     */
    public function getLevelIconAttribute(): string
    {
        return $this->level_info['icon'] ?? 'film';
    }

    /**
     * Get tier badge CSS classes
     */
    public function getLevelBadgeClassAttribute(): string
    {
        return $this->level_info['bg_class'] ?? 'bg-zinc-700/40 text-zinc-300 border-zinc-600/40';
    }
}

