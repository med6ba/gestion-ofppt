<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    protected $fillable = ['name', 'code', 'description'];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
