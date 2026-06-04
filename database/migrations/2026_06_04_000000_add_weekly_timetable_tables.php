<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->date('week_start_date')->index();
            $table->date('week_end_date');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['group_id', 'week_start_date']);
        });

        Schema::table('timetable_sessions', function (Blueprint $table) {
            $table->foreignId('weekly_timetable_id')->nullable()->after('id')
                  ->constrained('weekly_timetables')->nullOnDelete();
            $table->string('cancellation_reason')->nullable()->after('change_note');
            $table->foreignId('cancelled_by')->nullable()->after('cancellation_reason')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });

        // Auto-create WeeklyTimetable records for existing sessions
        $existingSessions = DB::table('timetable_sessions')
            ->select('group_id', 'starts_on', 'ends_on', 'created_by')
            ->whereNull('weekly_timetable_id')
            ->distinct()
            ->get()
            ->groupBy(fn ($s) => $s->group_id . '_' . Carbon::parse($s->starts_on)->startOfWeek()->toDateString());

        foreach ($existingSessions as $key => $sessions) {
            $first = $sessions->first();
            $weekStart = Carbon::parse($first->starts_on)->startOfWeek();
            $wt = DB::table('weekly_timetables')->insertGetId([
                'group_id' => $first->group_id,
                'week_start_date' => $weekStart->toDateString(),
                'week_end_date' => $weekStart->copy()->addDays(5)->toDateString(),
                'status' => 'published',
                'created_by' => $first->created_by ?? 1,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('timetable_sessions')
                ->where('group_id', $first->group_id)
                ->whereDate('starts_on', '>=', $weekStart)
                ->whereDate('starts_on', '<=', $weekStart->copy()->addDays(6))
                ->whereNull('weekly_timetable_id')
                ->update(['weekly_timetable_id' => $wt]);
        }

        Schema::create('session_cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_session_id')->constrained('timetable_sessions')->cascadeOnDelete();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_cancellation_requests');

        Schema::table('timetable_sessions', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['weekly_timetable_id']);
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancellation_reason', 'weekly_timetable_id']);
        });

        Schema::dropIfExists('weekly_timetables');
    }
};
