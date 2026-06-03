<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['name', 'code', 'capacity', 'type'];

    public function sessions(): HasMany
    {
        return $this->hasMany(TimetableSession::class);
    }
}
