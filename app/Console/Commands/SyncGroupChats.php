<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Group;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

class SyncGroupChats extends Command
{
    protected $signature = 'chat:sync-groups';
    protected $description = 'Sync group chats for formateurs and students';

    public function handle()
    {
        $this->info('Starting group chat sync...');

        $formateurs = User::where('role', User::ROLE_FORMATEUR)->with('teachingGroups')->get();

        foreach ($formateurs as $formateur) {
            foreach ($formateur->teachingGroups as $group) {
                $moduleId = $group->pivot->module_id;

                $conversation = Conversation::firstOrCreate([
                    'type' => 'group',
                    'group_id' => $group->id,
                    'module_id' => $moduleId,
                ], [
                    'title' => $group->code . ' - Module ' . $moduleId,
                    'created_by' => $formateur->id,
                ]);

                // Sync participants
                $participants = collect([$formateur->id => ['role_in_conversation' => 'admin']]);

                $students = User::where('role', User::ROLE_STAGIAIRE)
                    ->where('group_id', $group->id)
                    ->get();

                foreach ($students as $student) {
                    $participants->put($student->id, ['role_in_conversation' => 'participant']);
                }

                $conversation->participants()->syncWithoutDetaching($participants->toArray());
            }
        }

        $this->info('Group chat sync completed successfully!');
    }
}
