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
use App\Notifications\SmartCampusNotification;
use App\Services\ChatAccessService;
use Illuminate\Support\Facades\Notification;

test('local app rejects custom host domains', function () {
    $this->get('http://eve.manar.com:8000/login')
        ->assertNotFound();
});

test('demo directeur can sign in and reach dashboard', function () {
    $this->seed();

    $this->post('/login', [
        'email' => 'directeur@ofppt.test',
        'password' => 'password',
    ])->assertRedirect(route('directeur.dashboard'));
});

test('pending stagiaire cannot access the app', function () {
    $this->seed();

    $this->post('/login', [
        'email' => 'pending@ofppt.test',
        'password' => 'password',
    ])->assertRedirect(route('approval.pending'));
});

test('surveillant timetable creation blocks room conflicts', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();
    $existing = TimetableSession::first();
    $room = Room::whereKey($existing->room_id)->first();

    $this->actingAs($surveillant)->post(route('timetable.store'), [
        'group_id' => $existing->group_id,
        'module_id' => $existing->module_id,
        'formateur_id' => $existing->formateur_id,
        'room_id' => $room->id,
        'day_of_week' => $existing->day_of_week,
        'starts_on' => $existing->starts_on->format('Y-m-d'),
        'ends_on' => $existing->ends_on->format('Y-m-d'),
        'week_number' => $existing->week_number,
        'starts_at' => substr($existing->starts_at, 0, 5),
        'ends_at' => substr($existing->ends_at, 0, 5),
    ])->assertSessionHasErrors('starts_at');
});

test('surveillant publishes a weekly group timetable and emails all approved roles', function () {
    $this->seed();
    Notification::fake();

    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();
    $group = Group::where('code', 'DD101')->first();
    $formateur = User::where('email', 'amina.formateur@ofppt.test')->first();
    $module = TrainingModule::where('code', 'M-NET')->first();
    $room = Room::where('code', 'S18')->first();
    $weekStart = now()->addWeeks(3)->startOfWeek();

    $this->actingAs($surveillant)->post(route('timetable.store'), [
        'group_id' => $group->id,
        'module_id' => $module->id,
        'formateur_id' => $formateur->id,
        'room_id' => $room->id,
        'day_of_week' => 6,
        'starts_on' => $weekStart->copy()->addDay()->toDateString(),
        'starts_at' => '11:00',
        'ends_at' => '12:30',
    ])->assertRedirect(route('timetable.index', [
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

    User::approved()
        ->where('enabled', true)
        ->get()
        ->each(fn (User $user) => Notification::assertSentTo(
            $user,
            SmartCampusNotification::class,
            fn ($notification, array $channels) => in_array('database', $channels, true) && in_array('mail', $channels, true)
        ));

    Notification::assertNotSentTo(User::where('email', 'pending@ofppt.test')->first(), SmartCampusNotification::class);
});

test('surveillant cannot create sunday timetable sessions', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();
    $existing = TimetableSession::first();
    $weekStart = now()->addWeeks(4)->startOfWeek();

    $this->actingAs($surveillant)->post(route('timetable.store'), [
        'group_id' => $existing->group_id,
        'module_id' => $existing->module_id,
        'formateur_id' => $existing->formateur_id,
        'room_id' => $existing->room_id,
        'day_of_week' => 7,
        'starts_on' => $weekStart->toDateString(),
        'starts_at' => '08:30',
        'ends_at' => '10:30',
    ])->assertSessionHasErrors('day_of_week');
});

test('timetable pages render weekly grid layout', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();
    $formateur = User::where('email', 'formateur@ofppt.test')->first();

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

    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();
    $directeur = User::where('email', 'directeur@ofppt.test')->first();
    $formateur = User::where('email', 'formateur@ofppt.test')->first();
    $existing = TimetableSession::where('formateur_id', $formateur->id)->first();
    $nextWeekStart = now()->addWeek()->startOfWeek();

    $publishedSession = TimetableSession::create([
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

    $this->actingAs($surveillant)->post(route('timetable.active-week'), [
        'group_id' => $publishedSession->group_id,
        'week_start' => $nextWeekStart->toDateString(),
    ])->assertRedirect(route('timetable.index', [
        'group_id' => $publishedSession->group_id,
        'week_start' => $nextWeekStart->toDateString(),
    ]));

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

    $stagiaire = User::where('email', 'stagiaire@ofppt.test')->first();
    $directeur = User::where('email', 'directeur@ofppt.test')->first();

    expect(app(ChatAccessService::class)->canMessage($stagiaire, $directeur))->toBeFalse();
});

test('surveillant can open pdf aligned attendance reports', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();

    $this->actingAs($surveillant)
        ->get(route('attendance.reports'))
        ->assertOk()
        ->assertSee('Repeated absences')
        ->assertSee('Suspicious attempts');
});

test('formateur attendance session shows qr and statistics', function () {
    $this->seed();

    $formateur = User::where('email', 'formateur@ofppt.test')->first();
    $session = TimetableSession::where('formateur_id', $formateur->id)->first();

    $this->actingAs($formateur)
        ->get(route('attendance.show', $session))
        ->assertOk()
        ->assertSee('QR / code attendance')
        ->assertSee('Attendance rate');
});

test('qr refresh rotates token without recreating attendance session', function () {
    $this->seed();

    $formateur = User::where('email', 'formateur@ofppt.test')->first();
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

    $formateur = User::where('email', 'formateur@ofppt.test')->first();
    $stagiaire = User::where('email', 'stagiaire@ofppt.test')->first();
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

    $formateur = User::where('email', 'formateur@ofppt.test')->first();
    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();
    $stagiaire = User::where('email', 'ahmed.risk@ofppt.test')->first();
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

    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();

    $this->actingAs($surveillant)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertSee('Smart Campus Connect')
        ->assertSee('Backend access checks');
});
