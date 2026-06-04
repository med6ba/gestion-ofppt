<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyTimetable extends Model
{
    protected $fillable = [
        'group_id', 'week_start_date', 'week_end_date', 'title', 'notes',
        'status', 'created_by', 'published_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'week_end_date' => 'date',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function sessions(): HasMany { return $this->hasMany(TimetableSession::class); }

    public function scopePublished($query) { return $query->where('status', 'published'); }
    public function scopeDraft($query) { return $query->where('status', 'draft'); }
    public function scopeArchived($query) { return $query->where('status', 'archived'); }
    public function scopeForGroup($query, int $groupId) { return $query->where('group_id', $groupId); }
    public function scopeForWeek($query, Carbon $weekStart) { return $query->whereDate('week_start_date', $weekStart->startOfWeek()); }

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isPublished(): bool { return $this->status === 'published'; }
    public function isArchived(): bool { return $this->status === 'archived'; }

    public function publish(): void
    {
        $this->update(['status' => 'published', 'published_at' => now()]);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived', 'archived_at' => now()]);
    }

    public function duplicate(Carbon $newWeekStart): self
    {
        $newWeekStart = $newWeekStart->copy()->startOfWeek();
        $newWeekEnd = $newWeekStart->copy()->addDays(5);
        $isCurrentOrPast = $newWeekStart->startOfDay()->lte(now()->startOfWeek()->startOfDay());
        
        $newTimetable = $this->replicate(['published_at', 'archived_at']);
        $newTimetable->fill([
            'week_start_date' => $newWeekStart,
            'week_end_date' => $newWeekEnd,
            'status' => $isCurrentOrPast ? 'published' : 'draft',
            'published_at' => $isCurrentOrPast ? now() : null,
            'created_by' => auth()->id(),
        ]);
        $newTimetable->save();

        foreach ($this->sessions()->where('status', '!=', 'cancelled')->get() as $session) {
            $newSession = $session->replicate(['cancelled_at', 'cancelled_by', 'cancellation_reason']);
            $newSession->fill([
                'weekly_timetable_id' => $newTimetable->id,
                'starts_on' => $newWeekStart->toDateString(),
                'ends_on' => $newWeekEnd->toDateString(),
                'week_number' => $newWeekStart->weekOfYear,
                'status' => 'scheduled',
                'created_by' => auth()->id(),
            ]);
            $newSession->save();
        }

        return $newTimetable;
    }
}
