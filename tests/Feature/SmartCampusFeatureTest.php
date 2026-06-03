<?php

use App\Models\Room;
use App\Models\TimetableSession;
use App\Models\User;
use App\Services\ChatAccessService;

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
        'ends_on' => $nextWeekStart->copy()->endOfWeek()->toDateString(),
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

test('chat screen shows secure smart campus connect shell', function () {
    $this->seed();

    $surveillant = User::where('email', 'surveillant@ofppt.test')->first();

    $this->actingAs($surveillant)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertSee('Smart Campus Connect')
        ->assertSee('Backend access checks');
});
