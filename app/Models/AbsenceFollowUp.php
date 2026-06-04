<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceFollowUp extends Model
{
    protected $guarded = [];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_by_system' => 'boolean',
    ];

    public function stagiaire()
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
