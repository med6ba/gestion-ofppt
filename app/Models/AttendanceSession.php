<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceSession extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_QR_CLOSED = 'qr_closed';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'timetable_session_id',
        'formateur_id',
        'actual_started_at',
        'qr_phase_minutes',
        'normal_late_until_minutes',
        'severe_late_until_minutes',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'actual_started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function timetableSession(): BelongsTo
    {
        return $this->belongsTo(TimetableSession::class);
    }

    public function formateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function qrTokens(): HasMany
    {
        return $this->hasMany(QrAttendanceSession::class);
    }

    public function activeQrToken(): HasOne
    {
        return $this->hasOne(QrAttendanceSession::class)
            ->where('expires_at', '>', now())
            ->latestOfMany();
    }

    public function qrClosesAt(): Carbon
    {
        return $this->actual_started_at->copy()->addMinutes($this->qr_phase_minutes);
    }

    public function normalLateClosesAt(): Carbon
    {
        return $this->actual_started_at->copy()->addMinutes($this->normal_late_until_minutes);
    }

    public function severeLateClosesAt(): Carbon
    {
        return $this->actual_started_at->copy()->addMinutes($this->severe_late_until_minutes);
    }

    public function delayMinutes(?Carbon $now = null): int
    {
        $now ??= now();

        return max(0, (int) floor(($now->getTimestamp() - $this->actual_started_at->getTimestamp()) / 60));
    }

    public function isQrPhaseOpen(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->status !== self::STATUS_CLOSED && $now->lt($this->qrClosesAt());
    }

    public function isNormalLateWindowOpen(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->status !== self::STATUS_CLOSED
            && $now->gte($this->qrClosesAt())
            && $now->lte($this->normalLateClosesAt());
    }

    public function isSevereLateWindowOpen(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->status !== self::STATUS_CLOSED
            && $now->gt($this->normalLateClosesAt())
            && $now->lte($this->severeLateClosesAt());
    }

    public function isLateDeclarationOpen(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->isNormalLateWindowOpen($now) || $this->isSevereLateWindowOpen($now);
    }

    public function refreshClockStatus(?Carbon $now = null): void
    {
        $now ??= now();

        if ($this->status === self::STATUS_CLOSED) {
            return;
        }

        if ($now->gte($this->qrClosesAt()) && $this->status === self::STATUS_OPEN) {
            $this->forceFill(['status' => self::STATUS_QR_CLOSED])->save();
        }
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', self::STATUS_CLOSED);
    }
}
