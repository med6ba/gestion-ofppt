<?php

use App\Http\Controllers\SmartCampus\AbsenceAuthorizationRequestController;
use App\Http\Controllers\SmartCampus\AiAssistantController;
use App\Http\Controllers\SmartCampus\AnnouncementController;
use App\Http\Controllers\SmartCampus\AttestationRequestController;
use App\Http\Controllers\SmartCampus\AttendanceController;
use App\Http\Controllers\SmartCampus\AuthController;
use App\Http\Controllers\SmartCampus\BadgeController;
use App\Http\Controllers\SmartCampus\ChatController;
use App\Http\Controllers\SmartCampus\DashboardController;
use App\Http\Controllers\SmartCampus\EvaluationController;
use App\Http\Controllers\SmartCampus\NotificationController;
use App\Http\Controllers\SmartCampus\ProfileController;
use App\Http\Controllers\SmartCampus\ResourceController;
use App\Http\Controllers\SmartCampus\TimetableController;
use App\Http\Controllers\SmartCampus\UserManagementController;
use App\Http\Controllers\SmartCampus\SettingsController;
use App\Http\Controllers\SmartCampus\SurveillantAbsenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');
Route::get('/qr-login/{token}', [AuthController::class, 'qrLogin'])->name('auth.qr-login');
Route::get('/lang/{locale}', function (string $locale, Request $request) {
    abort_unless(in_array($locale, config('app.supported_locales', ['fr', 'ar', 'en']), true), 404);

    session(['locale' => $locale]);
    app()->setLocale($locale);

    if ($request->user()) {
        $request->user()->forceFill(['preferred_locale' => $locale])->save();
    }

    return redirect()->back()->withCookie(cookie()->forever('locale', $locale));
})->name('lang.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/approval-pending', [AuthController::class, 'pending'])->name('approval.pending');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard.redirect');

    Route::get('/directeur/dashboard', [DashboardController::class, 'directeur'])
        ->middleware('role:directeur')
        ->name('directeur.dashboard');
    Route::get('/surveillant/dashboard', [DashboardController::class, 'surveillant'])
        ->middleware('role:surveillant')
        ->name('surveillant.dashboard');
    Route::get('/formateur/dashboard', [DashboardController::class, 'formateur'])
        ->middleware('role:formateur')
        ->name('formateur.dashboard');
    Route::get('/stagiaire/dashboard', [DashboardController::class, 'stagiaire'])
        ->middleware('role:stagiaire')
        ->name('stagiaire.dashboard');

    Route::get('/profile/{user}', [ProfileController::class, 'show'])
        ->name('profile.show');
    Route::put('/profile/{user}', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::get('/attestations/{attestation}/pdf', [AttestationRequestController::class, 'download'])->name('attestations.pdf');

    Route::middleware('role:directeur,surveillant')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/stagiaires/{user}/approve', [UserManagementController::class, 'approve'])->name('stagiaires.approve');
        Route::post('/stagiaires/{user}/reject', [UserManagementController::class, 'reject'])->name('stagiaires.reject');
        Route::get('/attendance/reports', [AttendanceController::class, 'reports'])->name('attendance.reports');
        Route::post('/attendance/settings', [AttendanceController::class, 'updateSettings'])->name('attendance.settings.update');

        Route::get('/requests/attestations', [AttestationRequestController::class, 'manage'])->name('attestations.manage');
        Route::post('/requests/attestations/{attestation}/approve', [AttestationRequestController::class, 'approve'])->name('attestations.approve');
        Route::post('/requests/attestations/{attestation}/reject', [AttestationRequestController::class, 'reject'])->name('attestations.reject');

        Route::get('/requests/absences', [AbsenceAuthorizationRequestController::class, 'manage'])->name('absences.manage');
        Route::post('/requests/absences/{absence}/approve', [AbsenceAuthorizationRequestController::class, 'approve'])->name('absences.approve');
        Route::post('/requests/absences/{absence}/reject', [AbsenceAuthorizationRequestController::class, 'reject'])->name('absences.reject');
    });

    Route::middleware('role:directeur')->group(function () {
        Route::post('/staff', [UserManagementController::class, 'storeStaff'])->name('staff.store');
    });

    Route::middleware('role:directeur,surveillant')->group(function () {
        Route::get('/timetable/manage', [TimetableController::class, 'index'])->name('timetable.index');
        Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
        Route::get('/surveillant/timetable', fn (Request $request) => redirect()->route('timetable.index', $request->query()));
        Route::get('/surveillant/resources', fn () => redirect()->route('resources.index'));
    });

    Route::middleware('role:surveillant')->group(function () {
        Route::post('/timetable/weekly', [TimetableController::class, 'storeWeeklyTimetable'])->name('timetable.weekly.store');
        Route::post('/timetable/weekly/{weeklyTimetable}/launch', [TimetableController::class, 'launchWeeklyTimetable'])->name('timetable.weekly.launch');
        Route::post('/timetable/weekly/{weeklyTimetable}/duplicate', [TimetableController::class, 'duplicateWeeklyTimetable'])->name('timetable.weekly.duplicate');
        Route::post('/timetable/weekly/{weeklyTimetable}/archive', [TimetableController::class, 'archiveWeeklyTimetable'])->name('timetable.weekly.archive');
        Route::post('/timetable/sessions', [TimetableController::class, 'storeSession'])->name('timetable.sessions.store');
        Route::put('/timetable/sessions/{session}', [TimetableController::class, 'updateSession'])->name('timetable.sessions.update');
        Route::delete('/timetable/sessions/{session}', [TimetableController::class, 'destroySession'])->name('timetable.sessions.destroy');
        Route::get('/timetable/cancellation-requests', [TimetableController::class, 'cancellationRequests'])->name('timetable.cancellations.index');
        Route::post('/timetable/cancellation-requests/{cancellationRequest}/approve', [TimetableController::class, 'approveCancellation'])->name('timetable.cancellations.approve');
        Route::post('/timetable/cancellation-requests/{cancellationRequest}/reject', [TimetableController::class, 'rejectCancellation'])->name('timetable.cancellations.reject');

        Route::post('/resources/filieres', [ResourceController::class, 'storeFiliere'])->name('resources.filieres.store');
        Route::post('/resources/groups', [ResourceController::class, 'storeGroup'])->name('resources.groups.store');
        Route::post('/resources/modules', [ResourceController::class, 'storeModule'])->name('resources.modules.store');
        Route::post('/resources/modules/{module}/settings', [ResourceController::class, 'updateModuleSettings'])->name('resources.modules.settings');
        Route::post('/resources/rooms', [ResourceController::class, 'storeRoom'])->name('resources.rooms.store');

        Route::post('/surveillant/attendance/severe-late/{attendance}/validate', [AttendanceController::class, 'validateSevereLate'])->name('attendance.severe-late.validate');
        Route::post('/surveillant/attendance/severe-late/{attendance}/reject', [AttendanceController::class, 'rejectSevereLate'])->name('attendance.severe-late.reject');
    });

    Route::middleware('role:formateur')->group(function () {
        Route::post('/formateur/timetable/sessions/{session}/cancel-request', [TimetableController::class, 'requestCancellation'])->name('timetable.sessions.cancel-request');
        Route::get('/formateur/absences', [TimetableController::class, 'formateurAbsences'])->name('formateur.absences');
        Route::get('/formateur/teaching', [DashboardController::class, 'formateurTeaching'])->name('formateur.teaching');
        Route::get('/formateur/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/formateur/attendance/{session}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::post('/formateur/attendance/{session}/manual', [AttendanceController::class, 'storeManual'])->name('attendance.manual.store');
        Route::post('/formateur/attendance/{session}/qr', [AttendanceController::class, 'generateQr'])->name('attendance.qr.generate');
        Route::post('/formateur/attendance/{session}/qr/refresh', [AttendanceController::class, 'refreshQr'])->name('attendance.qr.refresh');
        Route::post('/formateur/attendance/{session}/qr/stop', [AttendanceController::class, 'stopQr'])->name('attendance.qr.stop');
        Route::post('/formateur/attendance/{session}/late/{attendance}/validate', [AttendanceController::class, 'validateLate'])->name('attendance.late.validate');
        Route::post('/formateur/attendance/{session}/late/{attendance}/reject', [AttendanceController::class, 'rejectLate'])->name('attendance.late.reject');
        Route::post('/formateur/attendance/{session}/late/bulk-validate', [AttendanceController::class, 'bulkValidateLate'])->name('attendance.late.bulk-validate');
        Route::post('/formateur/attendance/{session}/late/bulk-reject', [AttendanceController::class, 'bulkRejectLate'])->name('attendance.late.bulk-reject');
        Route::post('/formateur/attendance/{session}/correction', [AttendanceController::class, 'correctQrMistake'])->name('attendance.correction.store');
        Route::post('/formateur/attendance/{session}/finalize', [AttendanceController::class, 'finalizeSession'])->name('attendance.finalize');
    });

    Route::middleware('role:stagiaire')->group(function () {
        Route::get('/stagiaire/badge', [BadgeController::class, 'show'])->name('stagiaire.badge');
        Route::get('/stagiaire/badge/pdf', [BadgeController::class, 'download'])->name('stagiaire.badge.pdf');
        Route::get('/stagiaire/modules', [DashboardController::class, 'stagiaireModules'])->name('stagiaire.modules');
        Route::get('/stagiaire/attestations', [AttestationRequestController::class, 'index'])->name('attestations.index');
        Route::post('/stagiaire/attestations', [AttestationRequestController::class, 'store'])->name('attestations.store');
        Route::get('/stagiaire/absences', [AbsenceAuthorizationRequestController::class, 'index'])->name('absences.index');
        Route::post('/stagiaire/absences', [AbsenceAuthorizationRequestController::class, 'store'])->name('absences.store');
        Route::get('/attendance/me', [AttendanceController::class, 'mine'])->name('attendance.mine');
        Route::get('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::get('/attendance/scan/{token}', [AttendanceController::class, 'scan'])->name('attendance.scan');
        Route::post('/attendance/code', [AttendanceController::class, 'storeCode'])->name('attendance.code.store');
        Route::post('/attendance/late', [AttendanceController::class, 'declareLate'])->name('attendance.late.declare');
    });
    Route::get('/surveillant/absences/suivi', [SurveillantAbsenceController::class, 'index'])
        ->middleware('role:directeur,surveillant')
        ->name('surveillant.absences.index');
    Route::get('/surveillant/absences/suivi/{followUp}', [SurveillantAbsenceController::class, 'show'])
        ->middleware('role:directeur,surveillant')
        ->name('surveillant.absences.show');
    Route::post('/surveillant/absences/suivi/{followUp}/resolve', [SurveillantAbsenceController::class, 'resolve'])
        ->middleware('role:directeur,surveillant')
        ->name('surveillant.absences.resolve');

    Route::get('/timetable', [TimetableController::class, 'mySchedule'])->name('timetable.mine');
    Route::get('/timetable/archive', [TimetableController::class, 'archive'])->name('timetable.archive');
    Route::get('/timetable/sessions/{session}', [TimetableController::class, 'sessionDetails'])->name('timetable.sessions.details');
    Route::get('/presence-xp', [AttendanceController::class, 'leaderboard'])->name('attendance.leaderboard');
    Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/evaluations/notes', [EvaluationController::class, 'gradeEntry'])
        ->middleware('role:formateur')
        ->name('evaluations.grades');
    Route::post('/evaluations/notes', [EvaluationController::class, 'storeGrades'])
        ->middleware('role:formateur')
        ->name('evaluations.grades.store');
    Route::get('/evaluations/statistiques', [EvaluationController::class, 'statistics'])->name('evaluations.statistics');
    Route::get('/evaluations/export/excel', [EvaluationController::class, 'exportExcel'])
        ->middleware('role:directeur,surveillant,formateur')
        ->name('evaluations.export.excel');
    Route::get('/evaluations/export/pdf', [EvaluationController::class, 'exportPdf'])
        ->middleware('role:directeur,surveillant,formateur')
        ->name('evaluations.export.pdf');
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])
        ->middleware('role:directeur,surveillant')
        ->name('announcements.store');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/absence-attachments/{absence}', [AbsenceAuthorizationRequestController::class, 'attachment'])->name('absences.attachment');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/conversations', [ChatController::class, 'start'])->name('chat.start');
    Route::get('/chat/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::get('/chat/conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages.index');

    Route::get('/campus-ai', [AiAssistantController::class, 'index'])->name('ai.index');
    Route::post('/campus-ai', [AiAssistantController::class, 'ask'])->name('ai.ask');
});

Route::view('/offline', 'pwa.offline')->name('offline');
