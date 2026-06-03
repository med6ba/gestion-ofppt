<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskScore extends Model
{
    protected $fillable = [
        'stagiaire_id',
        'score',
        'level',
        'absence_count',
        'late_count',
        'suspicious_count',
        'attendance_rate',
        'reasons',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'calculated_at' => 'datetime',
            'attendance_rate' => 'decimal:2',
        ];
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }
}
