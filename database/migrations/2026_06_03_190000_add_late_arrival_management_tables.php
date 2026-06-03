<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $attendanceStatuses = [
        'pending',
        'present',
        'absent',
        'late_pending',
        'late_validated',
        'late_rejected',
        'severe_late_pending',
        'severe_late_validated',
        'severe_late_rejected',
        'justified',
    ];

    private array $attendanceMethods = [
        'manual',
        'qr',
        'code',
        'late_declaration',
        'qr_correction',
        'finalization',
    ];

    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_session_id')->constrained('timetable_sessions')->cascadeOnDelete();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('actual_started_at')->index();
            $table->unsignedSmallInteger('qr_phase_minutes')->default(10);
            $table->unsignedSmallInteger('normal_late_until_minutes')->default(30);
            $table->unsignedSmallInteger('severe_late_until_minutes')->default(60);
            $table->enum('status', ['open', 'qr_closed', 'closed'])->default('open')->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['timetable_session_id', 'status']);
        });

        Schema::table('qr_attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('qr_attendance_sessions', 'attendance_session_id')) {
                $table->foreignId('attendance_session_id')->nullable()->after('id')->constrained('attendance_sessions')->nullOnDelete();
                $table->index(['attendance_session_id', 'expires_at']);
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'attendance_session_id')) {
                $table->foreignId('attendance_session_id')->nullable()->after('id')->constrained('attendance_sessions')->nullOnDelete();
            }

            if (!Schema::hasColumn('attendances', 'check_in_at')) {
                $table->timestamp('check_in_at')->nullable()->after('marked_at')->index();
            }

            if (!Schema::hasColumn('attendances', 'delay_minutes')) {
                $table->unsignedSmallInteger('delay_minutes')->nullable()->after('check_in_at');
            }

            if (!Schema::hasColumn('attendances', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('delay_minutes')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('attendances', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by')->index();
            }

            if (!Schema::hasColumn('attendances', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('validated_at');
            }

            $table->index(['attendance_session_id', 'stagiaire_id']);
        });

        $this->setEnum('attendances', 'status', array_merge($this->attendanceStatuses, ['late']), 'pending');
        DB::table('attendances')->where('status', 'late')->update(['status' => 'late_validated']);
        $this->setEnum('attendances', 'status', $this->attendanceStatuses, 'pending');
        $this->setEnum('attendances', 'method', $this->attendanceMethods, 'manual');
        $this->backfillLegacyQrAttendanceSessions();

        Schema::create('attendance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->foreignId('attendance_session_id')->nullable()->constrained('attendance_sessions')->nullOnDelete();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->timestamp('created_at')->nullable();
            $table->index(['attendance_session_id', 'created_at']);
        });

        Schema::create('presence_xp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->unique()->constrained('attendances')->nullOnDelete();
            $table->integer('points');
            $table->string('reason');
            $table->timestamp('created_at')->nullable();
            $table->index(['stagiaire_id', 'created_at']);
        });

        Schema::create('student_presence_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->integer('xp_points')->default(0);
            $table->unsignedSmallInteger('attendance_streak')->default(0);
            $table->unsignedSmallInteger('absence_count')->default(0);
            $table->unsignedSmallInteger('late_count')->default(0);
            $table->unsignedSmallInteger('severe_late_count')->default(0);
            $table->string('rank_level')->default('Bronze');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('attendance_settings')->insert([
            ['key' => 'qr_phase_minutes', 'value' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'normal_late_until_minutes', 'value' => '30', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'severe_late_until_minutes', 'value' => '60', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
        Schema::dropIfExists('student_presence_profiles');
        Schema::dropIfExists('presence_xp_logs');
        Schema::dropIfExists('attendance_audit_logs');

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }

            if (Schema::hasColumn('attendances', 'validated_at')) {
                $table->dropColumn('validated_at');
            }

            if (Schema::hasColumn('attendances', 'validated_by')) {
                $table->dropConstrainedForeignId('validated_by');
            }

            if (Schema::hasColumn('attendances', 'delay_minutes')) {
                $table->dropColumn('delay_minutes');
            }

            if (Schema::hasColumn('attendances', 'check_in_at')) {
                $table->dropColumn('check_in_at');
            }

            if (Schema::hasColumn('attendances', 'attendance_session_id')) {
                $table->dropConstrainedForeignId('attendance_session_id');
            }
        });

        Schema::table('qr_attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('qr_attendance_sessions', 'attendance_session_id')) {
                $table->dropConstrainedForeignId('attendance_session_id');
            }
        });

        Schema::dropIfExists('attendance_sessions');
    }

    private function setEnum(string $table, string $column, array $values, string $default): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $escaped = collect($values)
            ->map(fn (string $value) => "'".str_replace("'", "''", $value)."'")
            ->implode(',');

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` ENUM({$escaped}) NOT NULL DEFAULT '{$default}'");
    }

    private function backfillLegacyQrAttendanceSessions(): void
    {
        $legacyQrSessions = DB::table('qr_attendance_sessions')
            ->whereNull('attendance_session_id')
            ->get();

        foreach ($legacyQrSessions as $qr) {
            $timetableSession = DB::table('timetable_sessions')->where('id', $qr->timetable_session_id)->first();

            if (!$timetableSession) {
                continue;
            }

            $startedAt = Carbon::parse($qr->created_at ?? now());
            $status = match (true) {
                $startedAt->copy()->addMinutes(60)->isPast() => 'closed',
                $startedAt->copy()->addMinutes(10)->isPast() => 'qr_closed',
                default => 'open',
            };

            $attendanceSessionId = DB::table('attendance_sessions')->insertGetId([
                'timetable_session_id' => $qr->timetable_session_id,
                'formateur_id' => $qr->created_by,
                'actual_started_at' => $startedAt,
                'qr_phase_minutes' => 10,
                'normal_late_until_minutes' => 30,
                'severe_late_until_minutes' => 60,
                'status' => $status,
                'closed_at' => $status === 'closed' ? $startedAt->copy()->addMinutes(60) : null,
                'created_at' => $qr->created_at,
                'updated_at' => now(),
            ]);

            DB::table('qr_attendance_sessions')
                ->where('id', $qr->id)
                ->update([
                    'attendance_session_id' => $attendanceSessionId,
                    'expires_at' => $startedAt->copy()->addMinutes(10),
                    'updated_at' => now(),
                ]);
        }
    }
};
