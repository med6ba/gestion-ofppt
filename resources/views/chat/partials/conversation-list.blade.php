<div id="chat-sidebar-container" class="flex h-full flex-col bg-white border-r border-slate-200 w-full md:w-80 lg:w-96 shrink-0 {{ $activeConversation ? 'hidden md:flex' : 'flex' }}">
    
    <!-- Header -->
    <div class="px-4 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
        <h2 class="text-xl font-bold text-slate-800">Chat</h2>
        
        <!-- Add Button (Opens existing modal) -->
        <button type="button" @click="addContactOpen = true" class="p-2 bg-blue-50 text-blue-600 rounded-full hover:bg-blue-100 transition-colors" title="Nouveau message">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>
    </div>

    <!-- Search & Filters -->
    <div class="p-3 border-b border-slate-100 shrink-0 space-y-3 bg-white">
        <!-- Search Form -->
        <form action="{{ route('chat.index') }}" method="GET" class="relative">
            <input type="hidden" name="filter" value="{{ request('filter', 'tous') }}">
            
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Rechercher..." 
                   class="block w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-colors placeholder-slate-400">
        </form>

        <!-- Filters Scrollable Row -->
        @php
            $currentFilter = request('filter', 'tous');
            $filters = [
                'tous' => 'Tous',
                'formateurs' => 'Formateurs',
                'stagiaires' => 'Stagiaires',
                'groupes' => 'Groupes',
                'non_lus' => 'Non lus',
                'lus' => 'Lus',
            ];
        @endphp
        <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-1">
            @foreach($filters as $key => $label)
                <a href="{{ route('chat.index', ['filter' => $key, 'search' => request('search')]) }}" 
                   class="whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-medium transition-colors border
                          {{ $currentFilter === $key 
                              ? 'bg-blue-600 text-white border-blue-600' 
                              : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Conversations List -->
    <div class="flex-1 overflow-y-auto bg-white custom-scrollbar">
        @forelse($conversations as $conv)
            @include('chat.partials.conversation-row', ['conversation' => $conv])
        @empty
            <div class="flex flex-col items-center justify-center h-full p-6 text-center text-slate-500">
                <svg class="size-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                </svg>
                <p class="text-sm font-medium">Aucune conversation trouvée.</p>
                @if(request('search') || request('filter') !== 'tous')
                    <p class="text-xs text-slate-400 mt-1">Essayez de modifier vos filtres de recherche.</p>
                @endif
            </div>
        @endforelse
    </div>
</div>

<style>
/* Hide scrollbar for Chrome, Safari and Opera */
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.hide-scrollbar {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}

/* Custom minimal scrollbar for conversations list */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background: #cbd5e1;
}
</style>
