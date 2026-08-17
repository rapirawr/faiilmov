<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'cover_image',
        'custom_watch_order_enabled',
        'source_tag',
        'created_by',
        'status',
        'takedown_reason',
        'taken_down_at',
        'taken_down_by',
    ];

    protected $casts = [
        'custom_watch_order_enabled' => 'boolean',
        'taken_down_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Collection $collection) {
            if (empty($collection->slug) && !empty($collection->name)) {
                $baseSlug = Str::slug($collection->name);
                $slug = $baseSlug ?: 'collection-' . rand(1000, 9999);
                $originalSlug = $slug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$originalSlug}-" . (++$counter);
                }

                $collection->slug = $slug;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_down_by');
    }

    public function collectionFilms(): HasMany
    {
        return $this->hasMany(CollectionFilm::class)->orderBy('sequence', 'asc');
    }

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, 'collection_films')
            ->withPivot('sequence', 'note', 'added_by', 'created_at', 'updated_at')
            ->orderBy('collection_films.sequence', 'asc')
            ->withTimestamps();
    }

    public function watchOrders(): HasMany
    {
        return $this->hasMany(CollectionWatchOrder::class)->orderBy('sequence', 'asc');
    }

    public function releaseWatchOrders(): HasMany
    {
        return $this->hasMany(CollectionWatchOrder::class)
            ->where('order_type', 'release')
            ->orderBy('sequence', 'asc');
    }

    public function chronologicalWatchOrders(): HasMany
    {
        return $this->hasMany(CollectionWatchOrder::class)
            ->where('order_type', 'chronological')
            ->orderBy('sequence', 'asc');
    }

    public function isTakenDown(): bool
    {
        return $this->status === 'takedown' || !empty($this->taken_down_at);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNull('taken_down_at');
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('status', 'private')->whereNull('taken_down_at');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft')->whereNull('taken_down_at');
    }

    public function scopeTakedown(Builder $query): Builder
    {
        return $query->where('status', 'takedown')->orWhereNotNull('taken_down_at');
    }

    public function scopeAuto(Builder $query): Builder
    {
        return $query->where('type', 'auto');
    }

    public function scopeFromPrompt(Builder $query): Builder
    {
        return $query->where('type', 'prompt');
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('type', 'manual');
    }

    public function scopeAccessibleBy(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query->where('status', 'published')->whereNull('taken_down_at');
        }

        if ($user->is_admin) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where(function ($sub) {
                $sub->where('status', 'published')->whereNull('taken_down_at');
            })->orWhere('created_by', $user->id);
        });
    }

    /**
     * Check if a user has permission to edit this collection in Studio Editor
     * Rules:
     * - Creator can always edit their own collection ($this->created_by === $user->id).
     * - If user is admin:
     *   - Can edit if collection is system/official ($this->created_by === null).
     *   - Can edit if the collection creator is also an admin ($this->creator && $this->creator->is_admin).
     *   - CANNOT edit if created by a regular user.
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) return false;

        // The creator can always edit their own collection
        if ($this->created_by === $user->id) {
            return true;
        }

        $isCurrentUserAdmin = (bool) $user->is_admin || ($user->role ?? null) === 'admin' || (method_exists($user, 'isAdmin') && $user->isAdmin());

        // If user is admin, only allowed if system collection or creator is also admin
        if ($isCurrentUserAdmin) {
            if ($this->created_by === null) {
                return true;
            }

            $creator = $this->relationLoaded('creator') ? $this->creator : $this->creator()->first();
            if ($creator) {
                return (bool) $creator->is_admin || ($creator->role ?? null) === 'admin' || (method_exists($creator, 'isAdmin') && $creator->isAdmin());
            }

            return true;
        }

        return false;
    }

    public function isOwner(?User $user): bool
    {
        if (!$user) return false;
        return $this->created_by === $user->id;
    }

    public function isAccessibleBy(?User $user): bool
    {
        if ($this->isTakenDown()) {
            return $user && ($user->is_admin || $this->created_by === $user->id);
        }

        if ($this->status === 'published') return true;
        return $this->canBeEditedBy($user);
    }
}
