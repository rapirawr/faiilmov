<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $fillable = ['query', 'result_count', 'ip_address', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
