<?php

namespace App\Events;

use App\Models\Film;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FilmUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Film $film)
    {
    }
}
