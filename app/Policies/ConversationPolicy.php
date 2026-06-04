<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatAccessService;

class ConversationPolicy
{
    public function __construct(private ChatAccessService $chatAccess)
    {}

    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->whereKey($user->id)->exists();
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $this->chatAccess->canMessageInConversation($user, $conversation);
    }
}
