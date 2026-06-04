<?php

use App\Models\Attendance;
use App\Models\AttendanceAuditLog;
use App\Models\AttendanceSession;
use App\Models\Group;
use App\Models\QrAttendanceSession;
use App\Models\Room;
use App\Models\TimetableSession;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\WeeklyTimetable;
use App\Services\ChatAccessService;

test('local app rejects custom host domains', function () {
    $this->get('http://eve.manar.com:8000/login')
        ->assertNotFound();
});

test('demo directeur can sign in and reach dashboard', function () {
    $this->seed();

    $this->post('/login', [
        'email' => 'directeur@ofppt-edu.ma',
        'password' => 'password',
    ])->assertRedirect(route('directeur.dashboard'));
});

test('pending stagiaire cannot access the app', function () {
    $this->seed();

    $this->post('/login', [
        'email' => 'pending@ofppt-edu.ma',
        'password' => 'password',
    ])->assertRedirect(route('approval.pending'));
});

test('surveillant timetable creation blocks room conflicts', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt-edu.ma')->first();
    $existing = TimetableSession::first();
    $room = Room::whereKey($existing->room_id)->first();

    $this->actingAs($surveillant)->postJson(route('timetable.sessions.store'), [
        'group_id' => $existing->group_id,
        'module_id' => $existing->module_id,
        'formateur_id' => $existing->formateur_id,
        'room_id' => $room->id,
        'day_of_week' => $existing->day_of_week,
        'week_start_date' => $existing->starts_on->startOfWeek()->format('Y-m-d'),
        'starts_at' => substr($existing->starts_at, 0, 5),
        'ends_at' => substr($existing->ends_at, 0, 5),
    ])->assertStatus(422);
});

test('surveillant creates a weekly group timetable session and syncs teaching assignments', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt-edu.ma')->first();
    $group = Group::where('code', 'DD101')->first();
    $formateur = User::where('email', 'amina.formateur@ofppt-edu.ma')->first();
    $module = TrainingModule::where('code', 'M-NET')->first();
    $room = Room::where('code', 'S18')->first();
    $weekStart = now()->addWeeks(3)->startOfWeek();

    $this->actingAs($surveillant)->postJson(route('timetable.sessions.store'), [
        'group_id' => $group->id,
        'module_id' => $module->id,
        'formateur_id' => $formateur->id,
        'room_id' => $room->id,
        'day_of_week' => 6,
        'week_start_date' => $weekStart->toDateString(),
        'starts_at' => '11:00',
        'ends_at' => '13:30',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect', route('timetable.index', [
            'group_id' => $group->id,
            'week_start' => $weekStart->toDateString(),
        ]));

    $created = TimetableSession::where('group_id', $group->id)
        ->where('formateur_id', $formateur->id)
        ->whereDate('starts_on', $weekStart->toDateString())
        ->latest('id')
        ->first();

    expect($created)->not->toBeNull();
    expect($created->day_of_week)->toBe(6);
    expect($created->ends_on->toDateString())->toBe($weekStart->copy()->addDays(5)->toDateString());
    expect($created->week_number)->toBe($weekStart->weekOfYear);
    expect($formateur->fresh()->teachingGroups()->where('groups.id', $group->id)->wherePivot('module_id', $module->id)->exists())->toBeTrue();
});

test('surveillant cannot create sunday timetable sessions', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt-edu.ma')->first();
    $existing = TimetableSession::first();
    $weekStart = now()->addWeeks(4)->startOfWeek();

    $this->actingAs($surveillant)->postJson(route('timetable.sessions.store'), [
        'group_id' => $existing->group_id,
        'module_id' => $existing->module_id,
        'formateur_id' => $existing->formateur_id,
        'room_id' => $existing->room_id,
        'day_of_week' => 7,
        'week_start_date' => $weekStart->toDateString(),
        'starts_at' => '08:30',
        'ends_at' => '10:30',
    ])->assertStatus(422);
});

test('timetable pages render weekly grid layout', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt-edu.ma')->first();
    $formateur = User::where('email', 'formateur@ofppt-edu.ma')->first();

    $this->actingAs($surveillant)
        ->get(route('timetable.index'))
        ->assertOk()
        ->assertSee('Emploi du Temps')
        ->assertSee('edt-grid', false)
        ->assertSee('LUNDI');

    $this->actingAs($formateur)
        ->get(route('timetable.mine'))
        ->assertOk()
        ->assertSee('edt-grid', false)
        ->assertSee('VENDREDI');
});

test('surveillant activates a week visible to all timetable viewers', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt-edu.ma')->first();
    $directeur = User::where('email', 'directeur@ofppt-edu.ma')->first();
    $formateur = User::where('email', 'formateur@ofppt-edu.ma')->first();
    $existing = TimetableSession::where('formateur_id', $formateur->id)->first();
    $nextWeekStart = now()->addWeek()->startOfWeek();

    $weeklyTimetable = WeeklyTimetable::create([
        'group_id' => $existing->group_id,
        'week_start_date' => $nextWeekStart->toDateString(),
        'week_end_date' => $nextWeekStart->copy()->addDays(5)->toDateString(),
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $surveillant->id,
    ]);

    $publishedSession = TimetableSession::create([
        'weekly_timetable_id' => $weeklyTimetable->id,
        'group_id' => $existing->group_id,
        'module_id' => $existing->module_id,
        'formateur_id' => $existing->formateur_id,
        'room_id' => $existing->room_id,
        'day_of_week' => 1,
        'starts_on' => $nextWeekStart->toDateString(),
        'ends_on' => $nextWeekStart->copy()->addDays(5)->toDateString(),
        'week_number' => $nextWeekStart->weekOfYear,
        'starts_at' => '08:30',
        'ends_at' => '10:30',
        'created_by' => $surveillant->id,
    ]);

    $stagiaire = User::role('stagiaire')
        ->approved()
        ->where('group_id', $publishedSession->group_id)
        ->first();

    $this->actingAs($directeur)
        ->get(route('timetable.mine'))
        ->assertOk()
        ->assertSee('Semaine '.$nextWeekStart->weekOfYear)
        ->assertSee($publishedSession->module->name)
        ->assertSee($publishedSession->group->code);

    $this->actingAs($formateur)
        ->get(route('timetable.mine'))
        ->assertOk()
        ->assertSee($publishedSession->module->name);

    $this->actingAs($stagiaire)
        ->get(route('timetable.mine'))
        ->assertOk()
        ->assertSee($publishedSession->module->name);
});

test('stagiaire cannot chat with directeur', function () {
    $this->seed();

    $stagiaire = User::where('email', 'stagiaire@ofppt-edu.ma')->first();
    $directeur = User::where('email', 'directeur@ofppt-edu.ma')->first();

    expect(app(ChatAccessService::class)->canMessage($stagiaire, $directeur))->toBeFalse();
});

test('surveillant can open pdf aligned attendance reports', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt-edu.ma')->first();

    $this->actingAs($surveillant)
        ->get(route('attendance.reports'))
        ->assertOk()
        ->assertSee('Repeated absences')
        ->assertSee('Suspicious attempts');
});

test('formateur attendance session shows qr and statistics', function () {
    $this->seed();

    $formateur = User::where('email', 'formateur@ofppt-edu.ma')->first();
    $session = TimetableSession::where('formateur_id', $formateur->id)->first();

    $this->actingAs($formateur)
        ->get(route('attendance.show', $session))
        ->assertOk()
        ->assertSee('QR / code attendance')
        ->assertSee('Attendance rate');
});

test('qr refresh rotates token without recreating attendance session', function () {
    $this->seed();

    $formateur = User::where('email', 'formateur@ofppt-edu.ma')->first();
    $session = TimetableSession::where('formateur_id', $formateur->id)
        ->whereHas('activeAttendanceSession')
        ->first();
    $attendanceSession = $session->activeAttendanceSession;
    $initialStartedAt = $attendanceSession->actual_started_at->toDateTimeString();
    $initialToken = $session->activeQrSession->secure_token;

    $this->actingAs($formateur)
        ->post(route('attendance.qr.refresh', $session))
        ->assertOk()
        ->assertJsonStructure(['qrDataUri', 'attendances', 'secondsRemaining']);

    expect(AttendanceSession::where('timetable_session_id', $session->id)->count())->toBe(1);
    expect($attendanceSession->fresh()->actual_started_at->toDateTimeString())->toBe($initialStartedAt);
    expect($session->activeQrSession()->first()->secure_token)->not->toBe($initialToken);
});

test('normal late declaration requires formateur validation before finalization', function () {
    $this->seed();

    $formateur = User::where('email', 'formateur@ofppt-edu.ma')->first();
    $stagiaire = User::where('email', 'stagiaire@ofppt-edu.ma')->first();
    $session = TimetableSession::where('formateur_id', $formateur->id)
        ->where('group_id', $stagiaire->group_id)
        ->first();
    $attendanceSession = AttendanceSession::where('timetable_session_id', $session->id)->first();
    $attendanceSession->update([
        'actual_started_at' => now()->subMinutes(12),
        'status' => 'qr_closed',
    ]);
    QrAttendanceSession::where('attendance_session_id', $attendanceSession->id)->delete();

    $this->actingAs($stagiaire)
        ->post(route('attendance.late.declare'), ['attendance_session_id' => $attendanceSession->id])
        ->assertRedirect(route('stagiaire.dashboard'));

    $attendance = Attendance::where('attendance_session_id', $attendanceSession->id)
        ->where('stagiaire_id', $stagiaire->id)
        ->first();
    expect($attendance->status)->toBe('late_pending');

    $this->actingAs($formateur)
        ->post(route('attendance.finalize', $session))
        ->assertSessionHasErrors('finalize');

    $this->actingAs($formateur)
        ->post(route('attendance.late.validate', [$session, $attendance]))
        ->assertRedirect();

    expect($attendance->fresh()->status)->toBe('late_validated');
    expect(AttendanceAuditLog::where('attendance_id', $attendance->id)->where('new_status', 'late_validated')->exists())->toBeTrue();

    $this->actingAs($formateur)
        ->post(route('attendance.finalize', $session))
        ->assertSessionHas('status');

    expect($attendanceSession->fresh()->status)->toBe('closed');
});

test('severe late declaration is escalated to surveillant general', function () {
    $this->seed();

    $formateur = User::where('email', 'formateur@ofppt-edu.ma')->first();
    $surveillant = User::where('email', 'surveillant@ofppt-edu.ma')->first();
    $stagiaire = User::where('email', 'ahmed.risk@ofppt-edu.ma')->first();
    $session = TimetableSession::where('formateur_id', $formateur->id)
        ->where('group_id', $stagiaire->group_id)
        ->first();
    $attendanceSession = AttendanceSession::where('timetable_session_id', $session->id)->first();
    $attendanceSession->update([
        'actual_started_at' => now()->subMinutes(45),
        'status' => 'qr_closed',
    ]);
    QrAttendanceSession::where('attendance_session_id', $attendanceSession->id)->delete();

    $this->actingAs($stagiaire)
        ->post(route('attendance.late.declare'), ['attendance_session_id' => $attendanceSession->id])
        ->assertRedirect(route('stagiaire.dashboard'));

    $attendance = Attendance::where('attendance_session_id', $attendanceSession->id)
        ->where('stagiaire_id', $stagiaire->id)
        ->first();
    expect($attendance->status)->toBe('severe_late_pending');

    $this->actingAs($formateur)
        ->post(route('attendance.late.validate', [$session, $attendance]))
        ->assertSessionHasErrors('late');

    $this->actingAs($surveillant)
        ->post(route('attendance.severe-late.validate', $attendance))
        ->assertRedirect();

    expect($attendance->fresh()->status)->toBe('severe_late_validated');
});

test('chat screen shows secure smart campus connect shell', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt-edu.ma')->first();

    $this->actingAs($surveillant)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertSee('Smart Campus Connect')
        ->assertSee('Backend access checks');
});
