<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stagiaire_id',
        'timetable_session_id',
        'ip_address',
        'reason',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TimetableSession::class, 'timetable_session_id');
    }
}
