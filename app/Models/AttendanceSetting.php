<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function minutes(string $key): int
    {
        $fallback = (int) config("smartcampus.attendance_windows.{$key}", match ($key) {
            'qr_phase_minutes' => 10,
            'normal_late_until_minutes' => 30,
            'severe_late_until_minutes' => 60,
            default => 0,
        });

        return (int) (static::query()->where('key', $key)->value('value') ?? $fallback);
    }

    public static function minuteSettings(): array
    {
        return [
            'qr_phase_minutes' => static::minutes('qr_phase_minutes'),
            'normal_late_until_minutes' => static::minutes('normal_late_until_minutes'),
            'severe_late_until_minutes' => static::minutes('severe_late_until_minutes'),
        ];
    }
}
