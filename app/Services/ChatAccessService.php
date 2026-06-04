<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ChatAccessService
{
    public function contactsFor(User $user): Collection
    {
        return User::query()
            ->whereKeyNot($user->id)
            ->where('approval_status', 'approved')
            ->where('enabled', true)
            ->when($user->isDirecteur(), fn ($query) => $query->whereIn('role', [User::ROLE_SURVEILLANT, User::ROLE_FORMATEUR]))
            ->when($user->isSurveillant(), fn ($query) => $query->whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_FORMATEUR]))
            ->when($user->isFormateur(), function ($query) use ($user) {
                $groupIds = $user->teachingGroups()->pluck('groups.id');
                $query->where(function ($nested) use ($groupIds) {
                    $nested->whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])
                        ->orWhere(function ($students) use ($groupIds) {
                            $students->where('role', User::ROLE_STAGIAIRE)->whereIn('group_id', $groupIds);
                        });
                });
            })
            ->when($user->isStagiaire(), function ($query) use ($user) {
                $query->where(function ($teachers) use ($user) {
                    $teachers->where('role', User::ROLE_FORMATEUR)
                        ->whereHas('teachingGroups', fn ($groups) => $groups->whereKey($user->group_id));
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->get();
    }

    public function groupConversationsFor(User $user): Collection
    {
        return Conversation::query()
            ->where('type', 'group')
            ->whereHas('participants', fn ($query) => $query->whereKey($user->id))
            ->get();
    }

    public function contactSectionsFor(User $user): SupportCollection
    {
        $contacts = $this->contactsFor($user);

        if ($user->isDirecteur()) {
            return collect([
                'Administration' => $contacts->filter(fn (User $contact) => $contact->isSurveillant())->values(),
                'Formateurs' => $contacts->filter(fn (User $contact) => $contact->isFormateur())->values(),
            ])->filter(fn ($items) => $items->isNotEmpty());
        }

        if ($user->isSurveillant()) {
            return collect([
                'Direction' => $contacts->filter(fn (User $contact) => $contact->isDirecteur())->values(),
                'Formateurs' => $contacts->filter(fn (User $contact) => $contact->isFormateur())->values(),
            ])->filter(fn ($items) => $items->isNotEmpty());
        }

        if ($user->isFormateur()) {
            return collect([
                'Administration' => $contacts->filter(fn (User $contact) => $contact->isDirecteur() || $contact->isSurveillant())->values(),
            ])->filter(fn ($items) => $items->isNotEmpty());
        }

        if ($user->isStagiaire()) {
            return collect([
                'Mes formateurs' => $contacts->filter(fn (User $contact) => $contact->isFormateur())->values(),
            ])->filter(fn ($items) => $items->isNotEmpty());
        }

        return collect();
    }

    public function teachingGroupsFor(User $user): SupportCollection
    {
        if (!$user->isFormateur()) {
            return collect();
        }

        return $user->teachingGroups()
            ->with(['stagiaires' => fn ($query) => $query->approved()->orderBy('name'), 'filiere'])
            ->orderBy('code')
            ->get()
            ->map(function (Group $group) use ($user) {
                return [
                    'group' => $group,
                    'module_id' => $group->pivot->module_id,
                    'students' => $group->stagiaires
                        ->filter(fn (User $student) => $this->canMessage($user, $student))
                        ->values(),
                ];
            });
    }

    public function canMessage(User $sender, User $receiver): bool
    {
        if ($sender->id === $receiver->id || !$receiver->isApproved() || !$receiver->enabled) {
            return false;
        }

        if ($sender->isDirecteur()) {
            return in_array($receiver->role, [User::ROLE_SURVEILLANT, User::ROLE_FORMATEUR], true);
        }

        if ($sender->isSurveillant()) {
            return in_array($receiver->role, [User::ROLE_DIRECTEUR, User::ROLE_FORMATEUR], true);
        }

        if ($sender->isFormateur()) {
            if (in_array($receiver->role, [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT], true)) {
                return true;
            }

            return $receiver->isStagiaire()
                && $sender->teachingGroups()->whereKey($receiver->group_id)->exists();
        }

        if ($sender->isStagiaire()) {
            return $receiver->isFormateur()
                && $receiver->teachingGroups()->whereKey($sender->group_id)->exists();
        }

        return false;
    }

    public function canMessageInConversation(User $user, Conversation $conversation): bool
    {
        if (!$conversation->participants->contains('id', $user->id)) {
            return false;
        }

        if ($conversation->type === 'group') {
            if ($user->isStagiaire()) {
                return Setting::get('allow_students_reply_in_group_chat', true);
            }
            return true; // Formateur can always message in group
        }

        return true;
    }

    public function findOrCreatePrivateConversation(User $sender, User $receiver): Conversation
    {
        $conversation = Conversation::query()
            ->where('type', 'private')
            ->whereHas('participants', fn ($query) => $query->whereKey($sender->id))
            ->whereHas('participants', fn ($query) => $query->whereKey($receiver->id))
            ->first();

        if ($conversation) {
            return $conversation;
        }

        $conversation = Conversation::query()->create([
            'type' => 'private',
            'created_by' => $sender->id,
        ]);

        $conversation->participants()->attach([
            $sender->id => ['last_read_at' => now(), 'role_in_conversation' => 'participant'],
            $receiver->id => ['last_read_at' => null, 'role_in_conversation' => 'participant'],
        ]);

        return $conversation;
    }
}
