<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE_PENDING = 'late_pending';
    public const STATUS_LATE_VALIDATED = 'late_validated';
    public const STATUS_LATE_REJECTED = 'late_rejected';
    public const STATUS_SEVERE_LATE_PENDING = 'severe_late_pending';
    public const STATUS_SEVERE_LATE_VALIDATED = 'severe_late_validated';
    public const STATUS_SEVERE_LATE_REJECTED = 'severe_late_rejected';
    public const STATUS_JUSTIFIED = 'justified';

    public const METHOD_MANUAL = 'manual';
    public const METHOD_QR = 'qr';
    public const METHOD_CODE = 'code';
    public const METHOD_LATE_DECLARATION = 'late_declaration';
    public const METHOD_QR_CORRECTION = 'qr_correction';
    public const METHOD_FINALIZATION = 'finalization';

    protected $fillable = [
        'attendance_session_id',
        'timetable_session_id',
        'stagiaire_id',
        'status',
        'method',
        'marked_by',
        'marked_at',
        'check_in_at',
        'delay_minutes',
        'validated_by',
        'validated_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'marked_at' => 'datetime',
            'check_in_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PRESENT,
            self::STATUS_ABSENT,
            self::STATUS_LATE_PENDING,
            self::STATUS_LATE_VALIDATED,
            self::STATUS_LATE_REJECTED,
            self::STATUS_SEVERE_LATE_PENDING,
            self::STATUS_SEVERE_LATE_VALIDATED,
            self::STATUS_SEVERE_LATE_REJECTED,
            self::STATUS_JUSTIFIED,
        ];
    }

    public static function acceptedStatuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_LATE_VALIDATED,
            self::STATUS_SEVERE_LATE_VALIDATED,
            self::STATUS_JUSTIFIED,
        ];
    }

    public static function lateStatuses(): array
    {
        return [
            self::STATUS_LATE_PENDING,
            self::STATUS_LATE_VALIDATED,
            self::STATUS_LATE_REJECTED,
            self::STATUS_SEVERE_LATE_PENDING,
            self::STATUS_SEVERE_LATE_VALIDATED,
            self::STATUS_SEVERE_LATE_REJECTED,
        ];
    }

    public static function severeLateStatuses(): array
    {
        return [
            self::STATUS_SEVERE_LATE_PENDING,
            self::STATUS_SEVERE_LATE_VALIDATED,
            self::STATUS_SEVERE_LATE_REJECTED,
        ];
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TimetableSession::class, 'timetable_session_id');
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AttendanceAuditLog::class);
    }

    public function isAccepted(): bool
    {
        return in_array($this->status, self::acceptedStatuses(), true);
    }

    public function isPendingLate(): bool
    {
        return in_array($this->status, [self::STATUS_LATE_PENDING, self::STATUS_SEVERE_LATE_PENDING], true);
    }
}
