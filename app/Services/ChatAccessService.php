<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Group;
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
            ->when($user->isDirecteur(), fn ($query) => $query->whereIn('role', [User::ROLE_SURVEILLANT, User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE]))
            ->when($user->isSurveillant(), fn ($query) => $query->whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE]))
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
                $query->where(function ($nested) use ($user) {
                    $nested->where('role', User::ROLE_SURVEILLANT)
                        ->orWhere(function ($teachers) use ($user) {
                            $teachers->where('role', User::ROLE_FORMATEUR)
                                ->whereHas('teachingGroups', fn ($groups) => $groups->whereKey($user->group_id));
                        });
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->get();
    }

    public function contactSectionsFor(User $user): SupportCollection
    {
        $contacts = $this->contactsFor($user);

        if ($user->isFormateur()) {
            return collect([
                'Administration' => $contacts->filter(fn (User $contact) => $contact->isDirecteur() || $contact->isSurveillant())->values(),
                'Stagiaires enseignés' => $contacts->filter(fn (User $contact) => $contact->isStagiaire())->values(),
            ])->filter(fn ($items) => $items->isNotEmpty());
        }

        if ($user->isStagiaire()) {
            return collect([
                'Mes formateurs' => $contacts->filter(fn (User $contact) => $contact->isFormateur())->values(),
                'Administration' => $contacts->filter(fn (User $contact) => $contact->isSurveillant())->values(),
            ])->filter(fn ($items) => $items->isNotEmpty());
        }

        return $contacts
            ->groupBy(fn (User $contact) => match ($contact->role) {
                User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT => 'Administration',
                User::ROLE_FORMATEUR => 'Formateurs',
                User::ROLE_STAGIAIRE => 'Stagiaires',
                default => 'Autres',
            });
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
            return in_array($receiver->role, [User::ROLE_SURVEILLANT, User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE], true);
        }

        if ($sender->isSurveillant()) {
            return in_array($receiver->role, [User::ROLE_DIRECTEUR, User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE], true);
        }

        if ($sender->isFormateur()) {
            if (in_array($receiver->role, [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT], true)) {
                return true;
            }

            return $receiver->isStagiaire()
                && $sender->teachingGroups()->whereKey($receiver->group_id)->exists();
        }

        if ($sender->isStagiaire()) {
            if ($receiver->isSurveillant()) {
                return true;
            }

            return $receiver->isFormateur()
                && $receiver->teachingGroups()->whereKey($sender->group_id)->exists();
        }

        return false;
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
            $sender->id => ['last_read_at' => now()],
            $receiver->id => ['last_read_at' => null],
        ]);

        return $conversation;
    }
}
