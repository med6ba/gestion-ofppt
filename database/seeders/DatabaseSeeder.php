<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceAttempt;
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
use App\Services\RiskScoreService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $dev = Filiere::create([
            'name' => 'Developpement Digital',
            'code' => 'DD',
            'description' => 'Digital development training path.',
        ]);

        $infra = Filiere::create([
            'name' => 'Infrastructure Digitale',
            'code' => 'ID',
            'description' => 'Networks, systems, and cloud infrastructure.',
        ]);

        $groupDev = Group::create([
            'filiere_id' => $dev->id,
            'name' => 'Developpement Digital 101',
            'code' => 'DD101',
            'year_level' => '1st year',
            'capacity' => 28,
        ]);

        $groupInfra = Group::create([
            'filiere_id' => $infra->id,
            'name' => 'Infrastructure Digitale 201',
            'code' => 'ID201',
            'year_level' => '2nd year',
            'capacity' => 24,
        ]);

        $php = TrainingModule::create(['name' => 'Laravel & PHP', 'code' => 'M-LAR']);
        $db = TrainingModule::create(['name' => 'Database Design', 'code' => 'M-DB']);
        $network = TrainingModule::create(['name' => 'Network Administration', 'code' => 'M-NET']);

        $roomA = Room::create(['name' => 'Salle 12', 'code' => 'S12', 'capacity' => 30, 'type' => 'classroom']);
        $lab = Room::create(['name' => 'Laboratoire Digital', 'code' => 'LAB-D', 'capacity' => 26, 'type' => 'lab']);
        $roomB = Room::create(['name' => 'Salle 18', 'code' => 'S18', 'capacity' => 28, 'type' => 'classroom']);

        $directeur = User::create([
            'name' => 'Directeur OFPPT',
            'email' => 'directeur@ofppt.test',
            'password' => $password,
            'role' => User::ROLE_DIRECTEUR,
            'approval_status' => 'approved',
        ]);

        $surveillant = User::create([
            'name' => 'Surveillant General',
            'email' => 'surveillant@ofppt.test',
            'password' => $password,
            'role' => User::ROLE_SURVEILLANT,
            'approval_status' => 'approved',
        ]);

        $formateur = User::create([
            'name' => 'Mohamed Formateur',
            'email' => 'formateur@ofppt.test',
            'password' => $password,
            'role' => User::ROLE_FORMATEUR,
            'approval_status' => 'approved',
            'phone' => '+212600000001',
        ]);

        $secondFormateur = User::create([
            'name' => 'Amina Formatrice',
            'email' => 'amina.formateur@ofppt.test',
            'password' => $password,
            'role' => User::ROLE_FORMATEUR,
            'approval_status' => 'approved',
        ]);

        $stagiaire = User::create([
            'name' => 'Youssef Stagiaire',
            'email' => 'stagiaire@ofppt.test',
            'password' => $password,
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $groupDev->id,
            'registration_number' => 'STG-001',
            'approval_status' => 'approved',
        ]);

        $pending = User::create([
            'name' => 'Pending Stagiaire',
            'email' => 'pending@ofppt.test',
            'password' => $password,
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $groupDev->id,
            'registration_number' => 'STG-002',
            'approval_status' => 'pending',
        ]);

        $ahmed = User::create([
            'name' => 'Ahmed Risk',
            'email' => 'ahmed.risk@ofppt.test',
            'password' => $password,
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $groupDev->id,
            'registration_number' => 'STG-003',
            'approval_status' => 'approved',
        ]);

        $salma = User::create([
            'name' => 'Salma Infrastructure',
            'email' => 'salma@ofppt.test',
            'password' => $password,
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $groupInfra->id,
            'registration_number' => 'STG-004',
            'approval_status' => 'approved',
        ]);

        $formateur->teachingGroups()->attach($groupDev->id, ['module_id' => $php->id]);
        $formateur->teachingGroups()->attach($groupInfra->id, ['module_id' => $db->id]);
        $formateur->teachingModules()->attach([$php->id, $db->id]);
        $secondFormateur->teachingGroups()->attach($groupInfra->id, ['module_id' => $network->id]);
        $secondFormateur->teachingModules()->attach([$network->id]);

        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();
        $today = now()->dayOfWeekIso;

        $todayMorning = TimetableSession::create([
            'group_id' => $groupDev->id,
            'module_id' => $php->id,
            'formateur_id' => $formateur->id,
            'room_id' => $lab->id,
            'day_of_week' => $today,
            'starts_on' => $weekStart,
            'ends_on' => $weekEnd,
            'week_number' => now()->weekOfYear,
            'starts_at' => '08:30',
            'ends_at' => '10:30',
            'created_by' => $surveillant->id,
        ]);

        $todayAfternoon = TimetableSession::create([
            'group_id' => $groupInfra->id,
            'module_id' => $db->id,
            'formateur_id' => $formateur->id,
            'room_id' => $roomA->id,
            'day_of_week' => $today,
            'starts_on' => $weekStart,
            'ends_on' => $weekEnd,
            'week_number' => now()->weekOfYear,
            'starts_at' => '14:00',
            'ends_at' => '16:00',
            'created_by' => $surveillant->id,
        ]);

        TimetableSession::create([
            'group_id' => $groupDev->id,
            'module_id' => $db->id,
            'formateur_id' => $formateur->id,
            'room_id' => $roomB->id,
            'day_of_week' => min(7, $today + 1),
            'starts_on' => $weekStart,
            'ends_on' => $weekEnd,
            'week_number' => now()->weekOfYear,
            'starts_at' => '10:45',
            'ends_at' => '12:45',
            'created_by' => $surveillant->id,
        ]);

        TimetableSession::create([
            'group_id' => $groupInfra->id,
            'module_id' => $network->id,
            'formateur_id' => $secondFormateur->id,
            'room_id' => $lab->id,
            'day_of_week' => min(7, $today + 1),
            'starts_on' => $weekStart,
            'ends_on' => $weekEnd,
            'week_number' => now()->weekOfYear,
            'starts_at' => '08:30',
            'ends_at' => '10:30',
            'created_by' => $surveillant->id,
        ]);

        QrAttendanceSession::create([
            'timetable_session_id' => $todayMorning->id,
            'group_id' => $groupDev->id,
            'secure_token' => Str::random(64),
            'short_code' => 'A7K92',
            'expires_at' => now()->addMinutes(60),
            'created_by' => $formateur->id,
        ]);

        Attendance::create([
            'timetable_session_id' => $todayAfternoon->id,
            'stagiaire_id' => $salma->id,
            'status' => 'late',
            'method' => 'manual',
            'marked_by' => $formateur->id,
            'marked_at' => now()->subHours(2),
        ]);

        for ($i = 1; $i <= 14; $i++) {
            $historical = TimetableSession::create([
                'group_id' => $groupDev->id,
                'module_id' => $i % 2 ? $php->id : $db->id,
                'formateur_id' => $formateur->id,
                'room_id' => $i % 2 ? $lab->id : $roomA->id,
                'day_of_week' => (($i % 5) + 1),
                'starts_on' => now()->subWeeks($i)->startOfWeek()->toDateString(),
                'ends_on' => now()->subWeeks($i)->endOfWeek()->toDateString(),
                'week_number' => now()->subWeeks($i)->weekOfYear,
                'starts_at' => '08:30',
                'ends_at' => '10:30',
                'created_by' => $surveillant->id,
            ]);

            Attendance::create([
                'timetable_session_id' => $historical->id,
                'stagiaire_id' => $ahmed->id,
                'status' => $i <= 12 ? 'absent' : 'late',
                'method' => 'manual',
                'marked_by' => $formateur->id,
                'marked_at' => now()->subWeeks($i),
            ]);

            Attendance::create([
                'timetable_session_id' => $historical->id,
                'stagiaire_id' => $stagiaire->id,
                'status' => $i % 6 === 0 ? 'late' : 'present',
                'method' => 'manual',
                'marked_by' => $formateur->id,
                'marked_at' => now()->subWeeks($i),
            ]);
        }

        AttendanceAttempt::create([
            'stagiaire_id' => $ahmed->id,
            'timetable_session_id' => $todayMorning->id,
            'ip_address' => '203.0.113.10',
            'reason' => 'ip_not_allowed',
            'metadata' => ['demo' => true],
            'created_at' => now()->subDay(),
        ]);

        app(RiskScoreService::class)->refreshAll();

        $conversation = Conversation::create([
            'type' => 'private',
            'created_by' => $surveillant->id,
            'last_message_at' => now()->subMinutes(5),
        ]);
        $conversation->participants()->attach([
            $surveillant->id => ['last_read_at' => now()->subMinutes(10)],
            $formateur->id => ['last_read_at' => now()],
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $surveillant->id,
            'body' => 'Bonjour, merci de valider les presences du groupe DD101 ce matin.',
            'is_read' => true,
            'created_at' => now()->subMinutes(12),
            'updated_at' => now()->subMinutes(12),
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $formateur->id,
            'body' => 'Bien recu. Le QR est pret pour la session Laravel.',
            'is_read' => false,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $pending->notify(new SmartCampusNotification(
            'Approval pending',
            'Your account is waiting for approval.',
            null,
            'approval'
        ));

        $stagiaire->notify(new SmartCampusNotification(
            'Schedule update',
            'Your Laravel session is planned today in LAB-D.',
            route('timetable.mine'),
            'schedule'
        ));

        $directeur->notify(new SmartCampusNotification(
            'Risk report ready',
            'Ahmed Risk is currently classified as High Risk for administrative follow-up.',
            route('attendance.reports'),
            'risk'
        ));
    }
}
