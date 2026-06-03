<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'module_id',
        'formateur_id',
        'room_id',
        'day_of_week',
        'starts_on',
        'ends_on',
        'week_number',
        'starts_at',
        'ends_at',
        'status',
        'change_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    public function formateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function activeAttendanceSession(): HasOne
    {
        return $this->hasOne(AttendanceSession::class)
            ->where('status', '!=', AttendanceSession::STATUS_CLOSED)
            ->latestOfMany();
    }

    public function activeQrSession(): HasOne
    {
        return $this->hasOne(QrAttendanceSession::class)->where('expires_at', '>', now())->latestOfMany();
    }

    public function occursOn(Carbon $date): bool
    {
        return (int) $this->day_of_week === $date->dayOfWeekIso
            && $date->betweenIncluded($this->starts_on, $this->ends_on);
    }

    public function timeLabel(): string
    {
        return substr($this->starts_at, 0, 5).' - '.substr($this->ends_at, 0, 5);
    }

    public function scopeForDate($query, Carbon $date)
    {
        if ($date->dayOfWeekIso > 6) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('day_of_week', $date->dayOfWeekIso)
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->where('status', '!=', 'cancelled');
    }

    public function scopeForWeek($query, Carbon $weekStart)
    {
        $weekEnd = $weekStart->copy()->startOfWeek()->addDays(5);

        return $query
            ->whereBetween('day_of_week', [1, 6])
            ->whereDate('starts_on', '<=', $weekEnd->toDateString())
            ->whereDate('ends_on', '>=', $weekStart->toDateString())
            ->where('status', '!=', 'cancelled');
    }
}
