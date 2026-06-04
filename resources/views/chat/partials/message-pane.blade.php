<div class="flex-1 flex flex-col h-full bg-white relative {{ !$activeConversation ? 'hidden md:flex' : 'flex' }}">
    @if($activeConversation)
        @php
            $isGroup = $activeConversation->type === 'group';
            if (!$isGroup) {
                $otherUser = $activeConversation->otherParticipant(auth()->user());
                $avatarText = $otherUser ? $otherUser->name : 'Utilisateur';
                $subText = $otherUser ? $otherUser->roleLabel() : '';
                if ($otherUser && $otherUser->group) {
                    $subText .= ' • ' . $otherUser->group->code;
                }
            } else {
                $avatarText = $activeConversation->title ?? 'Groupe';
                $subText = 'Groupe';
                if ($activeConversation->module) {
                    $subText .= ' • ' . $activeConversation->module->name;
                }
            }
        @endphp

        <!-- Conversation Header -->
        <div class="px-4 py-3 bg-white border-b border-slate-200 flex items-center justify-between shrink-0 shadow-sm z-10 relative">
            <div class="flex items-center gap-3">
                <!-- Mobile Back Button -->
                <a href="{{ route('chat.index', request()->only(['filter', 'search'])) }}" class="md:hidden p-2 -ml-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-colors">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                
                <!-- Avatar -->
                <div class="shrink-0">
                    @if(!$isGroup && $otherUser)
                        <x-user-avatar :user="$otherUser" size="md" />
                    @else
                        <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200 shadow-sm">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    @endif
                </div>
                
                <!-- Title & Subtitle -->
                <div>
                    <h3 class="font-bold text-slate-800">{{ $avatarText }}</h3>
                    <p class="text-xs font-medium text-slate-500">{{ $subText }}</p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-1 text-slate-400">
                <!-- Additional actions can go here (e.g. details, search in chat) -->
            </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-slate-50/50" id="messages-container" x-data x-init="$el.scrollTop = $el.scrollHeight">
            
            <!-- Loading History State (Optional, for infinite scroll later) -->
            <div class="flex justify-center my-4 hidden">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
            </div>

            @forelse($messages as $msg)
                @include('chat.partials.message-bubble', ['message' => $msg, 'conversation' => $activeConversation])
            @empty
                <div class="h-full flex flex-col items-center justify-center text-slate-500 p-6 text-center">
                    <div class="h-16 w-16 bg-blue-50 rounded-full flex items-center justify-center mb-4 text-blue-500">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <p class="font-medium text-slate-700">Aucun message</p>
                    <p class="text-sm text-slate-500 mt-1">Commencez la discussion en envoyant un message ci-dessous.</p>
                </div>
            @endforelse
            
            <!-- Dummy element to scroll to bottom -->
            <div id="messages-bottom"></div>
        </div>

        <!-- Composer -->
        @include('chat.partials.message-composer')
        
    @else
        <!-- Empty State (No Active Conversation) -->
        <div class="h-full flex flex-col items-center justify-center text-center p-8 bg-slate-50">
            <div class="bg-white p-6 rounded-full shadow-sm border border-slate-100 mb-6">
                <svg class="size-16 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Sélectionnez une discussion</h2>
            <p class="text-slate-500 max-w-sm">Choisissez une conversation dans la liste à gauche ou commencez une nouvelle discussion en cliquant sur le bouton "+".</p>
        </div>
    @endif
</div>

@if($activeConversation)
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const container = document.getElementById('messages-container');
                const bottomDummy = document.getElementById('messages-bottom');
                
                const scrollToBottom = () => {
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                };

                setTimeout(scrollToBottom, 100);

                // Setup Reverb / Echo listener
                if (window.Echo) {
                    window.Echo.private('chat.{{ $activeConversation->id }}')
                        .listen('.message.sent', (e) => {
                            // When a message is received from others, fetch the updated messages HTML
                            axios.get("{{ route('chat.messages.index', $activeConversation) }}?html=1", {
                                headers: { 'Accept': 'application/json' }
                            })
                            .then(res => {
                                if (res.data && res.data.html) {
                                    container.innerHTML = res.data.html + '<div id="messages-bottom"></div>';
                                    scrollToBottom();
                                    window.dispatchEvent(new CustomEvent('message-sent'));
                                }
                            })
                            .catch(err => console.error(err));
                        });
                }

                window.addEventListener('message-sent', (e) => {
                    if (e.detail && e.detail.html) {
                        bottomDummy.insertAdjacentHTML('beforebegin', e.detail.html);
                        scrollToBottom();
                        
                        // Remove empty state message if present
                        const emptyState = container.querySelector('.text-center.text-slate-500');
                        if (emptyState) {
                            emptyState.remove();
                        }
                    }
                });
            });
        </script>
    @endpush
@endif
