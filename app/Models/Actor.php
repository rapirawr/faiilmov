<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'photo_url'];

    public function films()
    {
        return $this->belongsToMany(Film::class, 'film_actor')->withPivot('character_name');
    }
}
