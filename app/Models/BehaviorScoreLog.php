<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BehaviorScoreLog extends Model
{
    protected $guarded = [];

    public function stagiaire()
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
