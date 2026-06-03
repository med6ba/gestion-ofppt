<x-layouts.app title="Users & Approvals">
    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Pending stagiaires</h2>
                    <p class="text-sm text-slate-500">Approve or reject self-registrations.</p>
                </div>
                <span class="sc-badge bg-campus-50 text-campus-700">{{ $pendingStagiaires->count() }}</span>
            </div>

            <div class="mt-4 grid gap-3">
                @forelse ($pendingStagiaires as $student)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $student->name }}</div>
                                <div class="text-sm text-slate-500">{{ $student->email }} | {{ $student->group?->code }}</div>
                            </div>
                            <div class="flex gap-2">
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
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No pending stagiaires.</p>
                @endforelse
            </div>
        </section>

        @if (auth()->user()->isDirecteur())
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Create staff account</h2>
                <form method="POST" action="{{ route('staff.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <div>
                        <label class="sc-label">Name</label>
                        <input class="sc-input mt-1" name="name" required>
                    </div>
                    <div>
                        <label class="sc-label">Email</label>
                        <input class="sc-input mt-1" name="email" type="email" required>
                    </div>
                    <div>
                        <label class="sc-label">Phone</label>
                        <input class="sc-input mt-1" name="phone">
                    </div>
                    <div>
                        <label class="sc-label">Role</label>
                        <select class="sc-input mt-1" name="role" required>
                            <option value="surveillant">Surveillant General</option>
                            <option value="formateur">Formateur</option>
                        </select>
                    </div>
                    <div>
                        <label class="sc-label">Password</label>
                        <input class="sc-input mt-1" name="password" type="password" required>
                    </div>
                    <div>
                        <label class="sc-label">Confirm password</label>
                        <input class="sc-input mt-1" name="password_confirmation" type="password" required>
                    </div>
                    <button class="sc-btn sc-btn-primary">Create account</button>
                </form>
            </section>
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Staff</h2>
            <div class="mt-4 grid gap-3">
                @foreach ($staff as $member)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $member->name }}</div>
                                <div class="text-sm text-slate-500">{{ $member->email }}</div>
                            </div>
                            <span class="sc-badge bg-slate-100 text-slate-700">{{ $member->roleLabel() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Stagiaires</h2>
            <div class="mt-4 grid gap-3">
                @foreach ($stagiaires as $student)
                    <a href="{{ route('profile.show', $student) }}" class="rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $student->name }}</div>
                                <div class="text-sm text-slate-500">{{ $student->group?->code }} | {{ $student->email }}</div>
                            </div>
                            <div class="flex gap-2">
                                <span class="sc-badge bg-slate-100 text-slate-700">{{ $student->approval_status }}</span>
                                @if ($student->riskScore)
                                    <span class="sc-badge {{ $student->riskScore->level === 'High' ? 'bg-rose-100 text-rose-700' : ($student->riskScore->level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $student->riskScore->level }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
