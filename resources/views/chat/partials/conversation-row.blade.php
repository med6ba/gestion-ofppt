@php
    $isActive = $activeConversation && $activeConversation->id === $conversation->id;
    $unreadCount = $conversation->unread_messages_count ?? 0;
    $lastMessage = $conversation->messages->first();
    $isGroup = $conversation->type === 'group';
    
    if (!$isGroup) {
        $otherUser = $conversation->otherParticipant(auth()->user());
        $avatarText = $otherUser ? $otherUser->name : 'Utilisateur';
        $subText = $otherUser ? $otherUser->roleLabel() : '';
        if ($otherUser && $otherUser->group) {
            $subText .= ' • ' . $otherUser->group->code;
        }
    } else {
        $avatarText = $conversation->title ?? 'Groupe';
        $subText = 'Groupe';
        if ($conversation->module) {
            $subText .= ' • ' . $conversation->module->name;
        }
    }
@endphp

<a href="{{ route('chat.show', $conversation->id) }}" 
   class="block p-3 border-b border-slate-100 transition-colors {{ $isActive ? 'bg-blue-50/50' : 'hover:bg-slate-50' }}">
    <div class="flex items-center gap-3">
        
        <!-- Avatar -->
        <div class="relative shrink-0">
            @if(!$isGroup && $otherUser)
                <x-user-avatar :user="$otherUser" size="md" />
            @else
                <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            @endif
            
            @if($unreadCount > 0)
                <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-[10px] font-bold text-white ring-2 ring-white">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </div>
        
        <!-- Content -->
        <div class="min-w-0 flex-1">
            <div class="flex justify-between items-baseline mb-0.5">
                <h3 class="truncate font-semibold {{ $unreadCount > 0 ? 'text-slate-900' : 'text-slate-800' }}">
                    {{ $avatarText }}
                </h3>
                @if($lastMessage)
                    <span class="text-[11px] whitespace-nowrap ml-2 {{ $unreadCount > 0 ? 'text-emerald-600 font-medium' : 'text-slate-500' }}">
                        {{ $lastMessage->created_at->format('H:i') }}
                    </span>
                @endif
            </div>
            
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-xs text-slate-500">
                    @if($isGroup)
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 mr-1 border border-slate-200">Groupe</span>
                    @endif
                    
                    @if($lastMessage)
                        <span class="{{ $unreadCount > 0 ? 'font-medium text-slate-700' : '' }}">
                            @if($lastMessage->sender_id === auth()->id())
                                <span class="text-slate-400">Vous:</span> 
                            @endif
                            {{ $lastMessage->body ? Str::limit($lastMessage->body, 30) : '📎 Pièce jointe' }}
                        </span>
                    @else
                        <span class="italic text-slate-400">Aucun message</span>
                    @endif
                </p>
                
                @if(!$isGroup && isset($subText))
                    <span class="shrink-0 text-[10px] font-medium text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">
                        {{ explode(' • ', $subText)[0] }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</a>
