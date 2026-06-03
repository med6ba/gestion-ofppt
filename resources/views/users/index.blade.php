<x-layouts.app title="Utilisateurs">
    @php
        $allTypes = [
            '' => 'Tous',
            'directeur' => 'Directeur',
            'surveillant' => 'Surveillant Général',
            'formateur' => 'Formateur',
            'stagiaire' => 'Stagiaire',
        ];
        $types = auth()->user()->isDirecteur()
            ? $allTypes
            : ['stagiaire' => 'Stagiaire'];
        $statusOptions = ['' => 'Tous statuts', 'pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected'];
        $roleBadge = fn (string $role) => match ($role) {
            'directeur' => 'bg-indigo-100 text-indigo-700',
            'surveillant' => 'bg-blue-100 text-blue-700',
            'formateur' => 'bg-emerald-100 text-emerald-700',
            'stagiaire' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <section class="sc-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold">Gestion utilisateurs</h2>
                <p class="text-sm text-slate-500">Recherche, filtrage par rôle et suivi du statut stagiaire.</p>
            </div>
            <span class="sc-badge bg-campus-50 text-campus-700">{{ $users->total() }} résultat(s)</span>
        </div>

        <div class="mt-5 flex gap-2 overflow-x-auto pb-1">
            @foreach ($types as $value => $label)
                @php
                    $active = ($filters['role'] ?? '') === $value;
                    $params = array_filter(['role' => $value, 'search' => $filters['search'] ?? null]);
                @endphp
                <a href="{{ route('users.index', $params) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition {{ $active ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                    @if ($value)
                        <span class="ml-1 opacity-75">{{ $roleCounts->get($value, 0) }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="mt-5 grid gap-3 lg:grid-cols-[1fr_220px_180px_auto]">
            <label>
                <span class="sc-label">Search</span>
                <input name="search" value="{{ $filters['search'] }}" class="sc-input mt-1" placeholder="Name, email, registration number">
            </label>
            <label>
                <span class="sc-label">User type</span>
                <select name="role" class="sc-input mt-1">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sc-label">Stagiaire status</span>
                <select name="status" class="sc-input mt-1" @disabled($filters['role'] !== 'stagiaire')>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2">
                <button class="sc-btn sc-btn-primary">Filter</button>
                <a href="{{ route('users.index') }}" class="sc-btn sc-btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="sc-card overflow-hidden p-0">
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Utilisateur</th>
                            <th class="px-4 py-3">Rôle</th>
                            <th class="px-4 py-3">Groupe</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Risque</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $member)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('profile.show', $member) }}" class="font-semibold text-slate-800 hover:text-primary">{{ $member->name }}</a>
                                    <div class="text-xs text-slate-500">{{ $member->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="sc-badge {{ $roleBadge($member->role) }}">{{ $member->roleLabel() }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->group?->code ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="sc-badge bg-slate-100 text-slate-700">{{ $member->approval_status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($member->riskScore)
                                        <span class="sc-badge {{ $member->riskScore->level === 'High' ? 'bg-rose-100 text-rose-700' : ($member->riskScore->level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $member->riskScore->level }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No users match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 p-4 md:hidden">
                @forelse ($users as $member)
                    <a href="{{ route('profile.show', $member) }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold">{{ $member->name }}</div>
                                <div class="truncate text-sm text-slate-500">{{ $member->email }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $member->group?->code ?? 'No group' }}</div>
                            </div>
                            <span class="sc-badge {{ $roleBadge($member->role) }}">{{ $member->roleLabel() }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="sc-badge bg-slate-100 text-slate-700">{{ $member->approval_status }}</span>
                            @if ($member->riskScore)
                                <span class="sc-badge {{ $member->riskScore->level === 'High' ? 'bg-rose-100 text-rose-700' : ($member->riskScore->level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $member->riskScore->level }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No users match these filters.</p>
                @endforelse
            </div>

            <div class="border-t border-slate-100 p-4">{{ $users->links() }}</div>
        </section>

        <aside class="space-y-6">
            <section class="sc-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">Stagiaires en attente</h2>
                        <p class="text-sm text-slate-500">Approve or reject self-registrations.</p>
                    </div>
                    <span class="sc-badge bg-campus-50 text-campus-700">{{ $pendingStagiaires->count() }}</span>
                </div>

                <div class="mt-4 grid gap-3">
                    @forelse ($pendingStagiaires as $student)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="font-semibold">{{ $student->name }}</div>
                            <div class="text-xs text-slate-500">{{ $student->email }} | {{ $student->group?->code }}</div>
                            <div class="mt-3 flex gap-2">
                                <form method="POST" action="{{ route('stagiaires.approve', $student) }}">
                                    @csrf
                                    <button class="sc-btn sc-btn-primary">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('stagiaires.reject', $student) }}">
                                    @csrf
                                    <button class="sc-btn sc-btn-danger">Reject</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No pending stagiaires.</p>
                    @endforelse
                </div>
            </section>

            @if (auth()->user()->isDirecteur())
                <section id="create-staff" class="sc-card p-5">
                    <h2 class="text-lg font-bold">Créer un utilisateur</h2>
                    <form method="POST" action="{{ route('staff.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        <label>
                            <span class="sc-label">Name</span>
                            <input class="sc-input mt-1" name="name" required>
                        </label>
                        <label>
                            <span class="sc-label">Email</span>
                            <input class="sc-input mt-1" name="email" type="email" required>
                        </label>
                        <label>
                            <span class="sc-label">Phone</span>
                            <input class="sc-input mt-1" name="phone">
                        </label>
                        <label>
                            <span class="sc-label">Role</span>
                            <select class="sc-input mt-1" name="role" required>
                                <option value="surveillant">Surveillant General</option>
                                <option value="formateur">Formateur</option>
                            </select>
                        </label>
                        <label>
                            <span class="sc-label">Password</span>
                            <input class="sc-input mt-1" name="password" type="password" required>
                        </label>
                        <label>
                            <span class="sc-label">Confirm password</span>
                            <input class="sc-input mt-1" name="password_confirmation" type="password" required>
                        </label>
                        <button class="sc-btn sc-btn-primary">Create account</button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
