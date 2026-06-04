<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentGrade extends Model
{
    protected $fillable = [
        'evaluation_id',
        'stagiaire_id',
        'score',
        'absent',
        'observation',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'absent' => 'boolean',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(GradeAuditLog::class);
    }
}
