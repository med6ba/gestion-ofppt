<x-layouts.app title="Chat" :collapse-sidebar="true">
    <div class="flex h-[calc(100vh-80px)] overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" 
         x-data="{ 
            addContactOpen: false, 
            selectedFile: null,
            refreshSidebar() {
                axios.get(window.location.href)
                    .then(res => {
                        const doc = new DOMParser().parseFromString(res.data, 'text/html');
                        const newSidebar = doc.getElementById('chat-sidebar-container');
                        const currentSidebar = document.getElementById('chat-sidebar-container');
                        if (newSidebar && currentSidebar) {
                            currentSidebar.innerHTML = newSidebar.innerHTML;
                        }
                    });
            }
         }"
         @notification-received.window="if($event.detail.type === 'message') refreshSidebar()"
         @message-sent.window="refreshSidebar()"
    >
        
        <!-- Left Sidebar: Conversations List -->
        @include('chat.partials.conversation-list')

        <!-- Right Main Panel: Active Conversation -->
        @include('chat.partials.message-pane')

        <!-- New Chat Modal -->
        <div x-show="addContactOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4" @click.self="addContactOpen = false">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-xl overflow-hidden flex flex-col max-h-[85vh]">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-lg text-slate-800">Nouveau message</h3>
                    <button type="button" @click="addContactOpen = false" class="text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-full p-1.5 transition-colors">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-3 custom-scrollbar">
                    @forelse ($contactSections as $sectionName => $sectionContacts)
                        <div class="mb-5">
                            <h4 class="px-3 py-1.5 text-[11px] font-bold uppercase text-slate-400 tracking-wider">{{ $sectionName }}</h4>
                            <div class="space-y-1">
                                @foreach($sectionContacts as $contact)
                                    <form method="POST" action="{{ route('chat.start') }}">
                                        @csrf
                                        <input type="hidden" name="receiver_id" value="{{ $contact->id }}">
                                        <button type="submit" class="flex w-full items-center gap-3 p-3 hover:bg-slate-50 rounded-xl transition border border-transparent hover:border-slate-100">
                                            <div class="shrink-0">
                                                <x-user-avatar :user="$contact" size="md" />
                                            </div>
                                            <div class="text-left flex-1 min-w-0">
                                                <div class="font-semibold text-slate-800 truncate">{{ $contact->name }}</div>
                                                <div class="text-xs text-slate-500 truncate">{{ $contact->roleLabel() }}{{ $contact->group ? ' • ' . $contact->group->code : '' }}</div>
                                            </div>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-500 flex flex-col items-center">
                            <div class="h-12 w-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                <svg class="size-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <p class="font-medium text-slate-700">Aucun contact disponible</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
