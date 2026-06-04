<x-layouts.app title="Paramètres">
    @php
        $user = auth()->user();
        $isDirecteur = $user->isDirecteur();
        
        $getSetting = fn($key, $default = null) => isset($settings) && isset($settings[$key]) ? $settings[$key]->value : $default;
    @endphp

    <div class="max-w-4xl mx-auto space-y-6" x-data="{ activeTab: 'compte' }">
        
        <!-- Header -->
        <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Paramètres</h1>
                <p class="text-sm text-slate-500">Gérez vos préférences et paramètres de compte.</p>
            </div>
            @if(session('success'))
                <div class="px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif
        </header>

        <!-- Profile Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col sm:flex-row items-center gap-6">
            <x-user-avatar :user="$user" size="xl" />
            <div class="flex-1 text-center sm:text-left">
                <h2 class="text-xl font-bold text-slate-800">{{ $user->name }}</h2>
                <p class="text-slate-500 mb-2">{{ $user->roleLabel() }}{{ $user->group ? ' • Groupe ' . $user->group->code : '' }}</p>
                <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">
                        <svg class="size-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $user->email }}
                    </span>
                    @if($user->phone)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">
                            <svg class="size-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $user->phone }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="shrink-0">
                <a href="{{ route('profile.show', $user) }}" class="sc-btn sc-btn-secondary">
                    Modifier le profil
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Sidebar Navigation -->
            <div class="md:block space-y-1">
                <button type="button" @click="activeTab = 'compte'" :class="activeTab === 'compte' ? 'bg-slate-50 text-emerald-600' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Compte
                </button>
                @if($isDirecteur)
                    <button type="button" @click="activeTab = 'ecole'" :class="activeTab === 'ecole' ? 'bg-slate-50 text-emerald-600' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        École
                    </button>
                    <button type="button" @click="activeTab = 'permissions'" :class="activeTab === 'permissions' ? 'bg-slate-50 text-emerald-600' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Permissions Chat
                    </button>
                @endif
            </div>

            <!-- Content Area -->
            <div class="col-span-1 md:col-span-2 space-y-6">
                
                <!-- Account Settings -->
                <section x-show="activeTab === 'compte'" x-cloak class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-lg font-bold text-slate-800">Paramètres du compte</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        
                        <div class="p-6 flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-slate-800">Langue de l'interface</h4>
                                <p class="text-sm text-slate-500 mt-1">Choisissez la langue d'affichage.</p>
                            </div>
                            <x-language-switcher />
                        </div>
                        
                        <div class="p-6 flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-slate-800">Mot de passe</h4>
                                <p class="text-sm text-slate-500 mt-1">Sécurisez votre compte avec un mot de passe fort.</p>
                            </div>
                            <a href="{{ route('profile.show', $user) }}" class="sc-btn sc-btn-secondary text-sm">Changer</a>
                        </div>
                        
                        <div class="p-6 flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-slate-800">Déconnexion</h4>
                                <p class="text-sm text-slate-500 mt-1">Fermez votre session sur cet appareil.</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="sc-btn !bg-red-50 text-red-600 hover:!bg-red-100 border-red-200 text-sm">Déconnexion</button>
                            </form>
                        </div>
                        
                    </div>
                </section>

                <!-- Admin Settings -->
                @if($isDirecteur)
                    <!-- Ecole Settings -->
                    <form x-show="activeTab === 'ecole'" x-cloak method="POST" action="{{ route('settings.store') }}" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        @csrf
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                            <h3 class="text-lg font-bold text-slate-800">École</h3>
                        </div>
                        
                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Durée d'actualisation QR Code (secondes)</label>
                                <input type="number" name="qr_refresh_interval" value="{{ $getSetting('qr_refresh_interval', 15) }}" class="sc-input w-full max-w-xs" min="5" max="60">
                                <p class="text-xs text-slate-500 mt-1">Le code QR de présence sera régénéré après cette durée.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Taille maximale des pièces jointes (MB)</label>
                                <input type="number" name="max_chat_attachment_mb" value="{{ $getSetting('max_chat_attachment_mb', 5) }}" class="sc-input w-full max-w-xs" min="1" max="50">
                                <p class="text-xs text-slate-500 mt-1">S'applique aux images et PDF dans le chat.</p>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="sc-btn sc-btn-primary">Enregistrer les paramètres</button>
                        </div>
                    </form>

                    <!-- Chat Permissions -->
                    <form x-show="activeTab === 'permissions'" x-cloak method="POST" action="{{ route('settings.store') }}" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        @csrf
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-slate-800">Permissions Chat</h3>
                        </div>
                        
                        <div class="p-6 space-y-5">
                            <div class="flex items-start gap-3">
                                <div class="flex items-center h-5 mt-1">
                                    <input type="hidden" name="allow_students_reply_in_group_chat" value="false">
                                    <input type="checkbox" name="allow_students_reply_in_group_chat" id="allow_students_reply_in_group_chat" value="true" class="size-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500" {{ filter_var($getSetting('allow_students_reply_in_group_chat', true), FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                </div>
                                <div>
                                    <label for="allow_students_reply_in_group_chat" class="font-semibold text-slate-700 block">Autoriser les stagiaires à répondre dans les groupes</label>
                                    <p class="text-sm text-slate-500">Si désactivé, seuls les formateurs pourront envoyer des messages dans les groupes de modules.</p>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="sc-btn sc-btn-primary">Enregistrer les paramètres</button>
                        </div>
                    </form>
                @endif
                
            </div>
        </div>
    </div>
</x-layouts.app>
