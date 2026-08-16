<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeatureBanner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'badge_text',
        'title',
        'description',
        'placeholder_text',
        'input_type',
        'button_text',
        'button_icon',
        'action_type',
        'action_url',
        'bg_gradient',
        'bg_gradient_from',
        'bg_gradient_to',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderByDesc('id');
    }

    public function getBackgroundStyleAttribute()
    {
        if ($this->bg_gradient === 'custom' && $this->bg_gradient_from && $this->bg_gradient_to) {
            return "background: linear-gradient(135deg, {$this->bg_gradient_from} 0%, rgba(9, 9, 11, 0.95) 50%, {$this->bg_gradient_to} 100%);";
        }
        return '';
    }

    public function getGradientClassesAttribute()
    {
        return match($this->bg_gradient) {
            'emerald_teal' => 'from-emerald-950/50 via-dark-900/90 to-teal-950/50 border-emerald-500/40',
            'sky_indigo'   => 'from-sky-950/50 via-dark-900/90 to-indigo-950/50 border-sky-500/40',
            'rose_orange'  => 'from-rose-950/50 via-dark-900/90 to-orange-950/50 border-rose-500/40',
            'cyber_neon'   => 'from-fuchsia-950/50 via-dark-900/90 to-cyan-950/50 border-fuchsia-500/40',
            'custom'       => 'border-amber-500/40',
            default        => 'from-amber-950/50 via-dark-900/90 to-purple-950/50 border-amber-500/40',
        };
    }
}
