<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'icon',
        'color',
        'xp_reward',
        'required_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'xp_reward' => 'integer',
        'required_count' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('unlocked_at');
    }

    /**
     * Get Lucide icon name ensuring safe fallback
     */
    public function getLucideIconAttribute(): string
    {
        return $this->icon ?: 'award';
    }

    /**
     * Category label formatted for UI
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'genre'     => 'Spesialis Genre',
            'habit'     => 'Kebiasaan Nonton',
            'community' => 'Sosial & Komunitas',
            default     => 'Pencapaian Utama',
        };
    }
}
