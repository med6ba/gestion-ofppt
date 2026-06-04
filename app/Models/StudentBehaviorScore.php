<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentBehaviorScore extends Model
{
    protected $guarded = [];

    public function stagiaire()
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }
}
