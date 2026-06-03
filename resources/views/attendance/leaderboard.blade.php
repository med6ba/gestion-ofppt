<x-layouts.app title="Presence XP">
    @php
        $user = auth()->user();
        $visibleProfiles = $user->isStagiaire() ? $profiles->take(5) : $profiles;
    @endphp

    @if ($user->isStagiaire() && $myProfile)
        <section class="mb-6 sc-card p-5">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <div class="text-sm font-medium text-slate-500">My XP</div>
                    <div class="mt-2 text-3xl font-bold">{{ $myProfile->xp_points }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-slate-500">My rank</div>
                    <div class="mt-2 text-3xl font-bold">#{{ $myRank }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-slate-500">Streak</div>
                    <div class="mt-2 text-3xl font-bold">{{ $myProfile->attendance_streak }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-slate-500">Level</div>
                    <div class="mt-2 text-3xl font-bold">{{ $myProfile->rank_level }}</div>
                </div>
            </div>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">{{ $user->isStagiaire() ? 'Top 5 Presence XP' : 'Leaderboard' }}</h2>
            <div class="mt-4 grid gap-3">
                @forelse ($visibleProfiles as $index => $profile)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold">
                                    #{{ $index + 1 }}
                                    {{ $user->isStagiaire() && $profile->stagiaire_id !== $user->id ? explode(' ', $profile->stagiaire->name)[0] : $profile->stagiaire->name }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">{{ $profile->stagiaire->group?->code }} | {{ $profile->rank_level }} | streak {{ $profile->attendance_streak }}</div>
                            </div>
                            <span class="sc-badge bg-campus-50 text-campus-700">{{ $profile->xp_points }} XP</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No XP data yet.</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Best groups</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($bestGroups as $group)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ $group->group_code }}</span>
                                <span class="sc-badge bg-campus-50 text-campus-700">{{ $group->average_xp }} XP avg</span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">Average streak {{ $group->average_streak }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No group data yet.</p>
                    @endforelse
                </div>
            </section>

            @unless ($user->isStagiaire())
                <section class="sc-card p-5">
                    <h2 class="text-lg font-bold">Students losing XP</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($riskProfiles as $profile)
                            <a href="{{ route('profile.show', $profile->stagiaire) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-semibold">{{ $profile->stagiaire->name }}</span>
                                    <span class="sc-badge bg-rose-100 text-rose-700">{{ $profile->xp_points }} XP</span>
                                </div>
                                <div class="mt-1 text-xs text-slate-500">{{ $profile->absence_count }} absences | {{ $profile->late_count }} retards</div>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">No negative XP profiles.</p>
                        @endforelse
                    </div>
                </section>
            @endunless
        </aside>
    </div>
</x-layouts.app>
