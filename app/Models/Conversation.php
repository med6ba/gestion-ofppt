<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['type', 'title', 'created_by', 'last_message_at', 'group_id', 'module_id'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at', 'role_in_conversation')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    public function otherParticipant(User $user): ?User
    {
        return $this->participants->firstWhere('id', '!=', $user->id);
    }

    public function scopeWithParticipantRole($query, $role, $currentUserId)
    {
        return $query->where('type', 'private')->whereHas('participants', function ($q) use ($role, $currentUserId) {
            $q->where('users.id', '!=', $currentUserId)->where('users.role', $role);
        });
    }

    public function scopeUnreadForUser($query, $userId)
    {
        return $query->whereHas('messages', function ($q) use ($userId) {
            $q->where('sender_id', '!=', $userId)->where('is_read', false);
        });
    }

    public function scopeReadForUser($query, $userId)
    {
        return $query->whereDoesntHave('messages', function ($q) use ($userId) {
            $q->where('sender_id', '!=', $userId)->where('is_read', false);
        });
    }
}
