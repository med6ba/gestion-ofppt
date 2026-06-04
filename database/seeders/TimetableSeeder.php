<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Room;
use App\Models\TimetableSession;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\WeeklyTimetable;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimetableSeeder extends Seeder
{
    public function run(): void
    {
        $surveillant = User::where('role', 'surveillant')->first() ?? User::factory()->create(['role' => 'surveillant']);
        $groups = Group::all();
        $modules = TrainingModule::all();
        $formateurs = User::where('role', 'formateur')->get();
        $rooms = Room::all();

        if ($groups->isEmpty() || $modules->isEmpty() || $formateurs->isEmpty() || $rooms->isEmpty()) {
            $this->command->warn('Missing required base data (groups, modules, formateurs, or rooms). Run DatabaseSeeder first.');
            return;
        }

        $slots = [
            ['start' => '08:30', 'end' => '11:00'],
            ['start' => '11:00', 'end' => '13:30'],
            ['start' => '13:30', 'end' => '16:00'],
            ['start' => '16:00', 'end' => '18:30'],
        ];

        // 4 weeks ago up to next week (6 weeks total)
        $weeks = [];
        for ($i = -4; $i <= 1; $i++) {
            $weeks[] = [
                'date' => now()->addWeeks($i)->startOfWeek(),
                'offset' => $i,
            ];
        }

        foreach ($weeks as $weekData) {
            $weekStart = $weekData['date'];
            $offset = $weekData['offset'];

            $status = 'published';
            if ($offset < 0) {
                $status = 'archived';
            } elseif ($offset > 0) {
                $status = 'draft';
            }

            foreach ($groups as $group) {
                $wt = WeeklyTimetable::firstOrCreate([
                    'group_id' => $group->id,
                    'week_start_date' => $weekStart->toDateString(),
                ], [
                    'week_end_date' => $weekStart->copy()->addDays(5)->toDateString(),
                    'status' => $status,
                    'title' => 'Planning Semaine ' . $weekStart->weekOfYear,
                    'created_by' => $surveillant->id,
                    'published_at' => $status === 'draft' ? null : ($offset < 0 ? $weekStart->copy()->subDays(2) : now()),
                    'archived_at' => $status === 'archived' ? $weekStart->copy()->addDays(6) : null,
                ]);

                // Generate 2-3 sessions per day for each group
                for ($day = 1; $day <= 5; $day++) {
                    $numSessions = rand(2, 3);
                    $selectedSlots = collect($slots)->random($numSessions);

                    foreach ($selectedSlots as $slot) {
                        TimetableSession::create([
                            'weekly_timetable_id' => $wt->id,
                            'group_id' => $group->id,
                            'module_id' => $modules->random()->id,
                            'formateur_id' => $formateurs->random()->id,
                            'room_id' => $rooms->random()->id,
                            'day_of_week' => $day,
                            'starts_on' => $weekStart->copy()->addDays($day - 1)->toDateString(),
                            'ends_on' => $weekStart->copy()->addDays(5)->toDateString(),
                            'starts_at' => $slot['start'],
                            'ends_at' => $slot['end'],
                            'week_number' => $weekStart->weekOfYear,
                            'status' => 'scheduled',
                            'created_by' => $surveillant->id,
                        ]);
                    }
                }
            }
        }
        $this->command->info('TimetableSeeder: Created 4 historical weeks, 1 current week, and 1 future week for ALL groups.');
    }
}
