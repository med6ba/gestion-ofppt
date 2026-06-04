<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionCancellationRequest extends Model
{
    protected $fillable = [
        'timetable_session_id', 'formateur_id', 'requested_by',
        'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function timetableSession(): BelongsTo { return $this->belongsTo(TimetableSession::class); }
    public function formateur(): BelongsTo { return $this->belongsTo(User::class, 'formateur_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeRejected($query) { return $query->where('status', 'rejected'); }

    public function isPending(): bool { return $this->status === 'pending'; }
}
