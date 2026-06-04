<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceAuthorizationRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'stagiaire_id',
        'absence_date',
        'starts_at',
        'ends_at',
        'reason',
        'attachment_path',
        'status',
        'surveillant_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'absence_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
