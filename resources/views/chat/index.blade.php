<x-layouts.app title="Internal Chat">
    <section class="sc-card mb-6 p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold">Smart Campus Connect</h2>
                <p class="mt-1 text-sm text-slate-500">Role-based internal chat for OFPPT administration, formateurs, and authorized stagiaire support.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="sc-badge bg-campus-50 text-campus-700">Backend access checks</span>
                <span class="sc-badge bg-emerald-50 text-emerald-700">Escaped messages</span>
                <span class="sc-badge bg-blue-50 text-blue-700">Polling fallback active</span>
            </div>
        </div>
    </section>

    <div class="grid min-h-[70vh] gap-6 lg:grid-cols-[300px_1fr]">
        <aside class="space-y-6">
            <section class="sc-card p-4">
                <h2 class="font-bold">Contacts</h2>
                <form method="POST" action="{{ route('chat.start') }}" class="mt-3 space-y-3">
                    @csrf
                    <select class="sc-input" name="receiver_id" required>
                        <option value="">Choose contact</option>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }} - {{ $contact->roleLabel() }}</option>
                        @endforeach
                    </select>
                    <button class="sc-btn sc-btn-primary w-full">Open chat</button>
                </form>
            </section>

            <section class="sc-card p-4">
                <h2 class="font-bold">Conversations</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($conversations as $conversation)
                        @php
                            $other = $conversation->participants->firstWhere('id', '!=', auth()->id());
                            $unread = (int) ($conversation->unread_messages_count ?? 0);
                        @endphp
                        <a href="{{ route('chat.show', $conversation) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50 {{ $activeConversation?->id === $conversation->id ? 'bg-campus-50' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-semibold">{{ $other?->name ?? $conversation->title ?? 'Conversation' }}</div>
                                @if ($unread > 0)
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700">{{ $unread > 9 ? '9+' : $unread }}</span>
                                @endif
                            </div>
                            <div class="mt-1 truncate text-xs text-slate-500">{{ $conversation->messages->first()?->body ?? 'No messages yet' }}</div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No conversations yet.</p>
                    @endforelse
                </div>
            </section>
        </aside>

        <section class="sc-card flex min-h-[70vh] flex-col overflow-hidden">
            @if ($activeConversation)
                @php $other = $activeConversation->participants->firstWhere('id', '!=', auth()->id()); @endphp
                <div class="border-b border-slate-200 p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold">{{ $other?->name ?? 'Conversation' }}</h2>
                            <p class="text-sm text-slate-500">{{ $other?->roleLabel() }}</p>
                        </div>
                        <span id="chatStatus" class="sc-badge bg-blue-50 text-blue-700" aria-live="polite">Live polling ready</span>
                    </div>
                </div>

                <div id="messageList" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4">
                    @foreach ($messages as $message)
                        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[82%] rounded-lg px-4 py-3 {{ $message->sender_id === auth()->id() ? 'bg-campus-600 text-white' : 'bg-white text-slate-800 shadow-sm' }}">
                                <div class="whitespace-pre-wrap text-sm">{{ $message->body }}</div>
                                <div class="mt-1 text-right text-[11px] opacity-70">{{ $message->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('chat.messages.store', $activeConversation) }}" class="border-t border-slate-200 p-4">
                    @csrf
                    <div class="flex gap-3">
                        <textarea class="sc-input min-h-12 flex-1 resize-none" name="body" rows="2" placeholder="Write a message" required></textarea>
                        <button class="sc-btn sc-btn-primary self-end">Send</button>
                    </div>
                </form>

                @push('scripts')
                    <script>
                        const list = document.getElementById('messageList');
                        const status = document.getElementById('chatStatus');
                        if (list) list.scrollTop = list.scrollHeight;

                        const currentUserId = {{ auth()->id() }};
                        const endpoint = @json(route('chat.messages.index', $activeConversation));
                        let isRefreshing = false;

                        const setStatus = (text, className = 'sc-badge bg-blue-50 text-blue-700') => {
                            if (!status) return;
                            status.className = className;
                            status.textContent = text;
                        };

                        const renderMessages = (messages) => {
                            if (!list) return;
                            list.innerHTML = '';
                            messages.forEach((message) => {
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
                            });
                            list.scrollTop = list.scrollHeight;
                        };

                        setInterval(() => {
                            if (isRefreshing) return;
                            isRefreshing = true;
                            setStatus('Syncing...');
                            fetch(endpoint, { headers: { 'Accept': 'application/json' } })
                                .then(response => response.json())
                                .then(data => {
                                    renderMessages(data.messages);
                                    setStatus(`Updated ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`, 'sc-badge bg-emerald-50 text-emerald-700');
                                })
                                .catch(() => setStatus('Connection retrying', 'sc-badge bg-amber-50 text-amber-700'))
                                .finally(() => { isRefreshing = false; });
                        }, 5000);
                    </script>
                @endpush
            @else
                <div class="flex flex-1 items-center justify-center p-8 text-center">
                    <div>
                        <h2 class="text-xl font-bold">Select a conversation</h2>
                        <p class="mt-2 text-sm text-slate-500">Contacts are filtered by role and group permissions.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
