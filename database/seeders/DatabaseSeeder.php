<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\AttendanceSession;
use App\Models\Conversation;
use App\Models\Filiere;
use App\Models\Group;
use App\Models\Message;
use App\Models\QrAttendanceSession;
use App\Models\Room;
use App\Models\TimetableSession;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\PresenceXpService;
use App\Services\RiskScoreService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $dev = Filiere::updateOrCreate(['code' => 'DD'], [
            'name' => 'Developpement Digital',
            'description' => 'Digital development training path.',
        ]);

        $infra = Filiere::updateOrCreate(['code' => 'ID'], [
            'name' => 'Infrastructure Digitale',
            'description' => 'Networks, systems, and cloud infrastructure.',
        ]);

        $groupDev = Group::updateOrCreate(['code' => 'DD101'], [
            'filiere_id' => $dev->id,
            'name' => 'Developpement Digital 101',
            'year_level' => '1st year',
            'capacity' => 28,
        ]);

        $groupInfra = Group::updateOrCreate(['code' => 'ID201'], [
            'filiere_id' => $infra->id,
            'name' => 'Infrastructure Digitale 201',
            'year_level' => '2nd year',
            'capacity' => 24,
        ]);

        $php = TrainingModule::updateOrCreate(['code' => 'M-LAR'], ['name' => 'Laravel & PHP']);
        $db = TrainingModule::updateOrCreate(['code' => 'M-DB'], ['name' => 'Database Design']);
        $network = TrainingModule::updateOrCreate(['code' => 'M-NET'], ['name' => 'Network Administration']);

        $roomA = Room::updateOrCreate(['code' => 'S12'], ['name' => 'Salle 12', 'capacity' => 30, 'type' => 'classroom']);
        $lab = Room::updateOrCreate(['code' => 'LAB-D'], ['name' => 'Laboratoire Digital', 'capacity' => 26, 'type' => 'lab']);
        $roomB = Room::updateOrCreate(['code' => 'S18'], ['name' => 'Salle 18', 'capacity' => 28, 'type' => 'classroom']);

        $directeur = $this->demoUser('directeur@ofppt-edu.ma', [
            'name' => 'Directeur OFPPT',
            'password' => $password,
            'role' => User::ROLE_DIRECTEUR,
            'approval_status' => 'approved',
        ], 'directeur@ofppt.test');

        $surveillant = $this->demoUser('surveillant@ofppt-edu.ma', [
            'name' => 'Surveillant General',
            'password' => $password,
            'role' => User::ROLE_SURVEILLANT,
            'approval_status' => 'approved',
        ], 'surveillant@ofppt.test');

        $formateur = $this->demoUser('formateur@ofppt-edu.ma', [
            'name' => 'Mohamed Formateur',
            'password' => $password,
            'role' => User::ROLE_FORMATEUR,
            'approval_status' => 'approved',
            'phone' => '+212600000001',
        ], 'formateur@ofppt.test');

        $secondFormateur = $this->demoUser('amina.formateur@ofppt-edu.ma', [
            'name' => 'Amina Formatrice',
            'password' => $password,
            'role' => User::ROLE_FORMATEUR,
            'approval_status' => 'approved',
        ], 'amina.formateur@ofppt.test');

        $stagiaire = $this->demoUser('stagiaire@ofppt-edu.ma', [
            'name' => 'Youssef Stagiaire',
            'password' => $password,
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $groupDev->id,
            'registration_number' => 'STG-001',
            'cni' => 'BE100001',
            'approval_status' => 'approved',
        ], 'stagiaire@ofppt.test');

        $pending = $this->demoUser('pending@ofppt-edu.ma', [
            'name' => 'Pending Stagiaire',
            'password' => $password,
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $groupDev->id,
            'registration_number' => 'STG-002',
            'cni' => 'BE100002',
            'approval_status' => 'pending',
        ], 'pending@ofppt.test');

        $ahmed = $this->demoUser('ahmed.risk@ofppt-edu.ma', [
            'name' => 'Ahmed Risk',
            'password' => $password,
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $groupDev->id,
            'registration_number' => 'STG-003',
            'cni' => 'BE100003',
            'approval_status' => 'approved',
        ], 'ahmed.risk@ofppt.test');

        $salma = $this->demoUser('salma@ofppt-edu.ma', [
            'name' => 'Salma Infrastructure',
            'password' => $password,
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $groupInfra->id,
            'registration_number' => 'STG-004',
            'cni' => 'BE100004',
            'approval_status' => 'approved',
        ], 'salma@ofppt.test');

        $formateur->teachingGroups()->syncWithoutDetaching([
            $groupDev->id => ['module_id' => $php->id],
            $groupInfra->id => ['module_id' => $db->id],
        ]);
        $formateur->teachingModules()->syncWithoutDetaching([$php->id, $db->id]);
        $secondFormateur->teachingGroups()->syncWithoutDetaching([
            $groupInfra->id => ['module_id' => $network->id],
        ]);
        $secondFormateur->teachingModules()->syncWithoutDetaching([$network->id]);

        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->startOfWeek()->addDays(5)->toDateString();
        $today = min(now()->dayOfWeekIso, 6);
        $nextTrainingDay = min(6, $today + 1);

        $todayMorning = TimetableSession::updateOrCreate([
            'group_id' => $groupDev->id,
            'module_id' => $php->id,
            'formateur_id' => $formateur->id,
            'room_id' => $lab->id,
            'day_of_week' => $today,
            'starts_on' => $weekStart,
            'starts_at' => '08:30',
            'ends_at' => '10:30',
        ], [
            'ends_on' => $weekEnd,
            'week_number' => now()->weekOfYear,
            'created_by' => $surveillant->id,
        ]);

        $todayAfternoon = TimetableSession::updateOrCreate([
            'group_id' => $groupInfra->id,
            'module_id' => $db->id,
            'formateur_id' => $formateur->id,
            'room_id' => $roomA->id,
            'day_of_week' => $today,
            'starts_on' => $weekStart,
            'starts_at' => '14:00',
            'ends_at' => '16:00',
        ], [
            'ends_on' => $weekEnd,
            'week_number' => now()->weekOfYear,
            'created_by' => $surveillant->id,
        ]);

        TimetableSession::updateOrCreate([
            'group_id' => $groupDev->id,
            'module_id' => $db->id,
            'formateur_id' => $formateur->id,
            'room_id' => $roomB->id,
            'day_of_week' => $nextTrainingDay,
            'starts_on' => $weekStart,
            'starts_at' => '10:45',
            'ends_at' => '12:45',
        ], [
            'ends_on' => $weekEnd,
            'week_number' => now()->weekOfYear,
            'created_by' => $surveillant->id,
        ]);

        TimetableSession::updateOrCreate([
            'group_id' => $groupInfra->id,
            'module_id' => $network->id,
            'formateur_id' => $secondFormateur->id,
            'room_id' => $lab->id,
            'day_of_week' => $nextTrainingDay,
            'starts_on' => $weekStart,
            'starts_at' => '08:30',
            'ends_at' => '10:30',
        ], [
            'ends_on' => $weekEnd,
            'week_number' => now()->weekOfYear,
            'created_by' => $surveillant->id,
        ]);

        $morningAttendanceSession = AttendanceSession::updateOrCreate([
            'timetable_session_id' => $todayMorning->id,
            'formateur_id' => $formateur->id,
        ], [
            'actual_started_at' => now(),
            'qr_phase_minutes' => 10,
            'normal_late_until_minutes' => 30,
            'severe_late_until_minutes' => 60,
            'status' => 'open',
        ]);

        QrAttendanceSession::updateOrCreate(['short_code' => 'A7K92'], [
            'attendance_session_id' => $morningAttendanceSession->id,
            'timetable_session_id' => $todayMorning->id,
            'group_id' => $groupDev->id,
            'secure_token' => Str::random(64),
            'expires_at' => $morningAttendanceSession->qrClosesAt(),
            'created_by' => $formateur->id,
        ]);

        $afternoonAttendanceSession = AttendanceSession::updateOrCreate([
            'timetable_session_id' => $todayAfternoon->id,
            'formateur_id' => $formateur->id,
        ], [
            'actual_started_at' => now()->subMinutes(40),
            'qr_phase_minutes' => 10,
            'normal_late_until_minutes' => 30,
            'severe_late_until_minutes' => 60,
            'status' => 'qr_closed',
        ]);

        Attendance::updateOrCreate([
            'timetable_session_id' => $todayAfternoon->id,
            'stagiaire_id' => $salma->id,
        ], [
            'attendance_session_id' => $afternoonAttendanceSession->id,
            'status' => 'severe_late_pending',
            'method' => 'late_declaration',
            'marked_by' => null,
            'marked_at' => now()->subMinutes(5),
            'check_in_at' => now()->subMinutes(5),
            'delay_minutes' => 40,
        ]);

        for ($i = 1; $i <= 14; $i++) {
            $historical = TimetableSession::updateOrCreate([
                'group_id' => $groupDev->id,
                'module_id' => $i % 2 ? $php->id : $db->id,
                'formateur_id' => $formateur->id,
                'room_id' => $i % 2 ? $lab->id : $roomA->id,
                'day_of_week' => (($i % 5) + 1),
                'starts_on' => now()->subWeeks($i)->startOfWeek()->toDateString(),
                'starts_at' => '08:30',
                'ends_at' => '10:30',
            ], [
                'ends_on' => now()->subWeeks($i)->startOfWeek()->addDays(5)->toDateString(),
                'week_number' => now()->subWeeks($i)->weekOfYear,
                'created_by' => $surveillant->id,
            ]);

            Attendance::updateOrCreate([
                'timetable_session_id' => $historical->id,
                'stagiaire_id' => $ahmed->id,
            ], [
                'status' => $i <= 12 ? 'absent' : 'late_validated',
                'method' => 'manual',
                'marked_by' => $formateur->id,
                'marked_at' => now()->subWeeks($i),
                'check_in_at' => now()->subWeeks($i),
            ]);

            Attendance::updateOrCreate([
                'timetable_session_id' => $historical->id,
                'stagiaire_id' => $stagiaire->id,
            ], [
                'status' => $i % 6 === 0 ? 'late_validated' : 'present',
                'method' => 'manual',
                'marked_by' => $formateur->id,
                'marked_at' => now()->subWeeks($i),
                'check_in_at' => now()->subWeeks($i),
            ]);
        }

        AttendanceAttempt::updateOrCreate([
            'stagiaire_id' => $ahmed->id,
            'timetable_session_id' => $todayMorning->id,
            'ip_address' => '203.0.113.10',
            'reason' => 'ip_not_allowed',
        ], [
            'metadata' => ['demo' => true],
            'created_at' => now()->subDay(),
        ]);

        app(RiskScoreService::class)->refreshAll();
        app(PresenceXpService::class)->refreshAll();

        $conversation = Conversation::where('type', 'private')
            ->where('created_by', $surveillant->id)
            ->whereHas('participants', fn ($query) => $query->whereKey($surveillant->id))
            ->whereHas('participants', fn ($query) => $query->whereKey($formateur->id))
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'private',
                'created_by' => $surveillant->id,
            ]);
        }

        $conversation->forceFill(['last_message_at' => now()->subMinutes(5)])->save();
        $conversation->participants()->syncWithoutDetaching([
            $surveillant->id => ['last_read_at' => now()->subMinutes(10)],
            $formateur->id => ['last_read_at' => now()],
        ]);

        Message::updateOrCreate([
            'conversation_id' => $conversation->id,
            'sender_id' => $surveillant->id,
            'body' => 'Bonjour, merci de valider les presences du groupe DD101 ce matin.',
        ], [
            'is_read' => true,
        ]);
        Message::updateOrCreate([
            'conversation_id' => $conversation->id,
            'sender_id' => $formateur->id,
            'body' => 'Bien recu. Le QR est pret pour la session Laravel.',
        ], [
            'is_read' => false,
        ]);

        $this->notifyOnce($pending, new SmartCampusNotification(
            'Approval pending',
            'Your account is waiting for approval.',
            null,
            'approval'
        ), 'Approval pending', 'approval');

        $this->notifyOnce($stagiaire, new SmartCampusNotification(
            'Schedule update',
            'Your Laravel session is planned today in LAB-D.',
            route('timetable.mine'),
            'schedule'
        ), 'Schedule update', 'schedule');

        $this->notifyOnce($directeur, new SmartCampusNotification(
            'Risk report ready',
            'Ahmed Risk is currently classified as High Risk for administrative follow-up.',
            route('attendance.reports'),
            'risk'
        ), 'Risk report ready', 'risk');
    }

    private function demoUser(string $email, array $attributes, ?string $legacyEmail = null): User
    {
        $user = User::withTrashed()->where('email', $email)->first();

        if (!$user && $legacyEmail) {
            $user = User::withTrashed()->where('email', $legacyEmail)->first();
        }

        if ($user) {
            $user->forceFill([...$attributes, 'email' => $email]);

            if (method_exists($user, 'restore') && $user->trashed()) {
                $user->restore();
            } else {
                $user->save();
            }

            if ($user->isStagiaire()) {
                $user->ensureBadgeCredentials();
            }

            return $user;
        }

        return User::create([...$attributes, 'email' => $email]);
    }

    private function notifyOnce(User $user, SmartCampusNotification $notification, string $title, string $category): void
    {
        $alreadySent = $user->notifications()
            ->where('type', SmartCampusNotification::class)
            ->get()
            ->contains(fn ($record) => ($record->data['title'] ?? null) === $title
                && ($record->data['category'] ?? null) === $category);

        if (!$alreadySent) {
            $user->notify($notification);
        }
    }
}
