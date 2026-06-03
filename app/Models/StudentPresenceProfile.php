<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPresenceProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stagiaire_id',
        'xp_points',
        'attendance_streak',
        'absence_count',
        'late_count',
        'severe_late_count',
        'rank_level',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }
}
