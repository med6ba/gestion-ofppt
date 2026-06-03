<x-layouts.app title="Chat" :collapse-sidebar="true">
    @php
        $currentUser = auth()->user();
        $activeOther = $activeConversation?->participants?->firstWhere('id', '!=', $currentUser->id);
        $initials = fn (?string $name) => strtoupper(substr($name ?? 'C', 0, 1));
        
        $getAvatarColor = function($role) {
            if ($role === 'stagiaire') return 'bg-pink-400 text-white';
            if ($role === 'formateur') return 'bg-blue-600 text-white';
            return 'bg-slate-300 text-slate-700';
        };
    @endphp

    <div x-data="{ addContactOpen: false }">
        <section class="mb-5 sc-card p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Smart Campus Connect</h2>
                    <p class="mt-1 text-sm text-slate-500">Messagerie securisee par role, groupe et relation pedagogique.</p>
                </div>
                <span class="sc-badge bg-emerald-50 text-emerald-700">Backend access checks</span>
            </div>
        </section>

        <div class="grid min-h-[72vh] gap-5 xl:grid-cols-[340px_1fr]">
            <aside class="space-y-5">
                <section class="sc-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 p-4">
                        <div>
                            <h2 class="font-bold">Conversations</h2>
                            <p class="text-xs text-slate-500">{{ $conversations->count() }} fil(s)</p>
                        </div>
                        @if ($currentUser->isFormateur())
                            <button type="button" class="sc-btn sc-btn-primary" @click="addContactOpen = true">
                                <x-ui.icon name="user-plus" size="size-4" class="mr-1" />
                                Ajouter
                            </button>
                        @endif
                    </div>
                    <div class="max-h-screen overflow-y-auto p-0">
                        @forelse ($conversations as $conversation)
                            @php
                                $other = $conversation->participants->firstWhere('id', '!=', $currentUser->id);
                                $unread = (int) ($conversation->unread_messages_count ?? 0);
                                $lastMessage = $conversation->messages->last();
                                $avatarClass = $getAvatarColor($other?->role);
                            @endphp
                            <a href="{{ route('chat.show', $conversation) }}" class="group relative flex items-start gap-4 border-b border-slate-100 p-4 hover:bg-slate-50 {{ $activeConversation?->id === $conversation->id ? 'bg-primary/5' : '' }}">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full {{ $avatarClass }} text-lg font-bold shadow-sm">
                                    {{ $initials($other?->name ?? $conversation->title) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-slate-800">{{ $other?->name ?? $conversation->title ?? 'Conversation' }}</div>
                                    <div class="mt-1 text-xs text-slate-400">
                                        @if ($other)
                                            <div class="truncate">{{ $other->role === 'stagiaire' ? 'Enseignements...' : 'Personnel' }}</div>
                                            @if ($other->group)
                                                <div class="truncate">{{ $other->group->code }}</div>
                                            @endif
                                            <div class="truncate">{{ $other->roleLabel() }}</div>
                                        @endif
                                        <div class="mt-1 text-slate-400">{{ $lastMessage?->created_at?->format('Y-m-d H:i:s') ?? '' }}</div>
                                    </div>
                                </div>
                                @if ($unread > 0)
                                    <div class="absolute right-4 top-1/2 flex size-3 -translate-y-1/2 items-center justify-center rounded-full bg-blue-100 ring-4 ring-white">
                                        <div class="size-2 rounded-full bg-blue-500"></div>
                                    </div>
                                @endif
                            </a>
                        @empty
                            <div class="p-4 text-sm text-slate-500">Aucune conversation. Ouvrez un contact pour commencer.</div>
                        @endforelse
                    </div>
                </section>

            <section class="sc-card overflow-hidden">
                <div class="border-b border-slate-100 p-4">
                    <h2 class="font-bold">Contacts autorises</h2>
                    <p class="text-xs text-slate-500">La liste est filtree par le backend.</p>
                </div>
                <div class="max-h-[30rem] overflow-y-auto p-3">
                    @forelse ($contactSections as $section => $sectionContacts)
                        <div class="mb-4">
                            <div class="mb-2 text-xs font-bold uppercase text-slate-400">{{ $section }}</div>
                            <div class="space-y-2">
                                @foreach ($sectionContacts as $contact)
                                    <form method="POST" action="{{ route('chat.start') }}">
                                        @csrf
                                        <input type="hidden" name="receiver_id" value="{{ $contact->id }}">
                                        <button class="flex w-full items-center gap-3 rounded-lg border border-slate-200 p-3 text-left hover:bg-slate-50">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ $initials($contact->name) }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-slate-800">{{ $contact->name }}</span>
                                                <span class="block text-xs text-slate-500">{{ $contact->roleLabel() }}{{ $contact->group ? ' | '.$contact->group->code : '' }}</span>
                                            </span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucun contact disponible.</p>
                    @endforelse
                </div>
            </section>

            @if ($currentUser->isFormateur() && $teachingGroups->count())
                <section class="sc-card overflow-hidden">
                    <div class="border-b border-slate-100 p-4">
                        <h2 class="font-bold">Mes groupes</h2>
                        <p class="text-xs text-slate-500">Groupes ou le formateur enseigne un module.</p>
                    </div>
                    <div class="max-h-80 overflow-y-auto p-3">
                        @foreach ($teachingGroups as $entry)
                            <details class="mb-2 rounded-lg border border-slate-200 p-3">
                                <summary class="cursor-pointer font-semibold text-slate-800">{{ $entry['group']->code }} - {{ $entry['group']->name }}</summary>
                                <div class="mt-3 space-y-2">
                                    @forelse ($entry['students'] as $student)
                                        <form method="POST" action="{{ route('chat.start') }}">
                                            @csrf
                                            <input type="hidden" name="receiver_id" value="{{ $student->id }}">
                                            <button class="flex w-full items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-left text-sm hover:bg-slate-100">
                                                <span>{{ $student->name }}</span>
                                                <span class="text-xs text-slate-400">{{ $student->registration_number }}</span>
                                            </button>
                                        </form>
                                    @empty
                                        <p class="text-xs text-slate-500">Aucun stagiaire approuve dans ce groupe.</p>
                                    @endforelse
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>

        <section class="sc-card flex min-h-[72vh] flex-col overflow-hidden">
            @if ($activeConversation)
                <header class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-lg bg-primary text-base font-bold text-white">{{ $initials($activeOther?->name) }}</span>
                        <div>
                            <h2 class="text-lg font-bold">{{ $activeOther?->name ?? 'Conversation' }}</h2>
                            <p class="text-sm text-slate-500">{{ $activeOther?->roleLabel() }}{{ $activeOther?->group ? ' | '.$activeOther->group->code : '' }}</p>
                        </div>
                    </div>
                    <span id="chatStatus" class="sc-badge bg-blue-50 text-blue-700" aria-live="polite">Live polling ready</span>
                </header>

                <div id="messageList" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4">
                    @foreach ($messages as $message)
                        <div class="flex {{ $message->sender_id === $currentUser->id ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[82%] rounded-lg px-4 py-3 {{ $message->sender_id === $currentUser->id ? 'bg-campus-600 text-white' : 'bg-white text-slate-800 shadow-sm' }}">
                                <div class="whitespace-pre-wrap text-sm">{{ $message->body }}</div>
                                <div class="mt-1 text-right text-[11px] opacity-70">{{ $message->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form id="chatForm" method="POST" action="{{ route('chat.messages.store', $activeConversation) }}" class="border-t border-slate-200 p-4">
                    @csrf
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <textarea id="chatInput" class="sc-input min-h-12 flex-1 resize-none" name="body" rows="2" placeholder="Write a message" required></textarea>
                        <button type="submit" id="chatSubmitBtn" class="sc-btn sc-btn-primary self-end">Send</button>
                    </div>
                </form>

                @push('scripts')
                    <script>
                        const list = document.getElementById('messageList');
                        const status = document.getElementById('chatStatus');
                        const form = document.getElementById('chatForm');
                        const input = document.getElementById('chatInput');
                        const submitBtn = document.getElementById('chatSubmitBtn');

                        if (list) list.scrollTop = list.scrollHeight;

                        const currentUserId = {{ $currentUser->id }};
                        const conversationId = {{ $activeConversation->id }};

                        const setStatus = (text, className = 'sc-badge bg-emerald-50 text-emerald-700') => {
                            if (!status) return;
                            status.className = className;
                            status.textContent = text;
                        };

                        const appendMessage = (message) => {
                            if (!list) return;
                            const row = document.createElement('div');
                            row.className = `flex ${message.sender_id === currentUserId ? 'justify-end' : 'justify-start'}`;

                            const bubble = document.createElement('div');
                            bubble.className = `max-w-[82%] rounded-lg px-4 py-3 ${message.sender_id === currentUserId ? 'bg-campus-600 text-white' : 'bg-white text-slate-800 shadow-sm'}`;

                            const body = document.createElement('div');
                            body.className = 'whitespace-pre-wrap text-sm';
                            body.textContent = message.body;

                            const time = document.createElement('div');
                            time.className = 'mt-1 text-right text-[11px] opacity-70';
                            time.textContent = message.created_at;

                            bubble.appendChild(body);
                            bubble.appendChild(time);
                            row.appendChild(bubble);
                            list.appendChild(row);
                            list.scrollTop = list.scrollHeight;
                        };

                        if (form) {
                            form.addEventListener('submit', (e) => {
                                e.preventDefault();
                                const body = input.value.trim();
                                if (!body) return;

                                input.disabled = true;
                                submitBtn.disabled = true;

                                fetch(form.action, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({ body })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.message) {
                                        appendMessage(data.message);
                                        input.value = '';
                                    }
                                })
                                .finally(() => {
                                    input.disabled = false;
                                    submitBtn.disabled = false;
                                    input.focus();
                                });
                            });
                            
                            input.addEventListener('keydown', (e) => {
                                if (e.key === 'Enter' && !e.shiftKey) {
                                    e.preventDefault();
                                    form.dispatchEvent(new Event('submit'));
                                }
                            });
                        }

                        if (window.Echo) {
                            setStatus('Connected (Real-time)');
                            window.Echo.private(`chat.${conversationId}`)
                                .listen('.message.sent', (e) => {
                                    if (e.messageData.sender_id !== currentUserId) {
                                        appendMessage(e.messageData);
                                    }
                                });
                        }
                    </script>
                @endpush
            @else
                <div class="flex flex-1 items-center justify-center p-8 text-center">
                    <div class="max-w-md">
                        <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-campus-50 text-campus-700">
                            <x-ui.icon name="chat-bubble" size="size-8" />
                        </div>
                        <h2 class="mt-4 text-xl font-bold">Select a conversation</h2>
                        <p class="mt-2 text-sm text-slate-500">Contacts are filtered by role and group permissions. Announcements stay in their own module.</p>
                    </div>
                </div>
            @endif
        </section>

        @if ($currentUser->isFormateur())
            <div x-show="addContactOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @keydown.escape.window="addContactOpen = false">
                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl" @click.outside="addContactOpen = false">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold">Ajouter un contact</h2>
                            <p class="text-sm text-slate-500">Uniquement les stagiaires que vous enseignez.</p>
                        </div>
                        <button type="button" class="manar-icon-btn" @click="addContactOpen = false">&times;</button>
                    </div>
                    <div class="mt-4 max-h-96 overflow-y-auto space-y-2">
                        @forelse ($contacts->filter(fn ($contact) => $contact->isStagiaire()) as $student)
                            <form method="POST" action="{{ route('chat.start') }}">
                                @csrf
                                <input type="hidden" name="receiver_id" value="{{ $student->id }}">
                                <button class="flex w-full items-center justify-between rounded-lg border border-slate-200 p-3 text-left hover:bg-slate-50">
                                    <span>
                                        <span class="block font-semibold">{{ $student->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $student->group?->code }} | {{ $student->registration_number }}</span>
                                    </span>
                                    <span class="text-xs font-semibold text-primary">Open</span>
                                </button>
                            </form>
                        @empty
                            <p class="text-sm text-slate-500">Aucun stagiaire disponible dans votre perimetre.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
    </div>
</x-layouts.app>
