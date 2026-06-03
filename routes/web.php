<?php

use App\Http\Controllers\SmartCampus\AiAssistantController;
use App\Http\Controllers\SmartCampus\AttendanceController;
use App\Http\Controllers\SmartCampus\AuthController;
use App\Http\Controllers\SmartCampus\ChatController;
use App\Http\Controllers\SmartCampus\DashboardController;
use App\Http\Controllers\SmartCampus\NotificationController;
use App\Http\Controllers\SmartCampus\ProfileController;
use App\Http\Controllers\SmartCampus\ResourceController;
use App\Http\Controllers\SmartCampus\TimetableController;
use App\Http\Controllers\SmartCampus\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard.redirect') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::post('/passkey/start', [AuthController::class, 'passkeyStart'])->name('passkey.start');
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

    Route::middleware('role:directeur,surveillant')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/stagiaires/{user}/approve', [UserManagementController::class, 'approve'])->name('stagiaires.approve');
        Route::post('/stagiaires/{user}/reject', [UserManagementController::class, 'reject'])->name('stagiaires.reject');
        Route::get('/attendance/reports', [AttendanceController::class, 'reports'])->name('attendance.reports');
    });

    Route::middleware('role:directeur')->group(function () {
        Route::post('/staff', [UserManagementController::class, 'storeStaff'])->name('staff.store');
    });

    Route::middleware('role:surveillant')->group(function () {
        Route::get('/surveillant/timetable', [TimetableController::class, 'index'])->name('timetable.index');
        Route::post('/surveillant/timetable', [TimetableController::class, 'store'])->name('timetable.store');
        Route::get('/surveillant/timetable/{session}/edit', [TimetableController::class, 'edit'])->name('timetable.edit');
        Route::put('/surveillant/timetable/{session}', [TimetableController::class, 'update'])->name('timetable.update');
        Route::delete('/surveillant/timetable/{session}', [TimetableController::class, 'destroy'])->name('timetable.destroy');

        Route::get('/surveillant/resources', [ResourceController::class, 'index'])->name('resources.index');
        Route::post('/surveillant/resources/filieres', [ResourceController::class, 'storeFiliere'])->name('resources.filieres.store');
        Route::post('/surveillant/resources/groups', [ResourceController::class, 'storeGroup'])->name('resources.groups.store');
        Route::post('/surveillant/resources/modules', [ResourceController::class, 'storeModule'])->name('resources.modules.store');
        Route::post('/surveillant/resources/rooms', [ResourceController::class, 'storeRoom'])->name('resources.rooms.store');
    });

    Route::middleware('role:formateur')->group(function () {
        Route::get('/formateur/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/formateur/attendance/{session}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::post('/formateur/attendance/{session}/manual', [AttendanceController::class, 'storeManual'])->name('attendance.manual.store');
        Route::post('/formateur/attendance/{session}/qr', [AttendanceController::class, 'generateQr'])->name('attendance.qr.generate');
    });

    Route::middleware('role:stagiaire')->group(function () {
        Route::get('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::get('/attendance/scan/{token}', [AttendanceController::class, 'scan'])->name('attendance.scan');
        Route::post('/attendance/code', [AttendanceController::class, 'storeCode'])->name('attendance.code.store');
    });

    Route::get('/timetable', [TimetableController::class, 'mySchedule'])->name('timetable.mine');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/conversations', [ChatController::class, 'start'])->name('chat.start');
    Route::get('/chat/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::get('/chat/conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages.index');

    Route::get('/campus-ai', [AiAssistantController::class, 'index'])->name('ai.index');
    Route::post('/campus-ai', [AiAssistantController::class, 'ask'])->name('ai.ask');
});

Route::view('/offline', 'pwa.offline')->name('offline');
